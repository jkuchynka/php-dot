<?php
namespace Jbizzay;

/**
 * Helps manage large arrays and allows for accessing, setting,
 * merging and unsetting with dot notation
 */
class Dot
{

    /**
     * Data
     *
     * @var array
     */
    protected $data;

    // @todo
    protected $preserveNumericKeys = false;

    /**
     * Create a new Dot
     * @param array|null $data
     */
    public function __construct($data = null)
    {
        $this->data = $data ?: array();
    }

    /**
     * Similar to get, but inits a value if not set
     *
     * @param string $key
     * @param mixed $dataOrCallable Any data value or callable
     * @return mixed Data
     */
    public function define($key, $dataOrCallable = array())
    {
        if ( ! $this->has($key)) {
            if (is_callable($dataOrCallable)) {
                $this->set($key, $dataOrCallable());
            } else {
                $this->set($key, $dataOrCallable);
            }
        }
        return $this->get($key);
    }

    /**
     * Get a value, returns default if not set
     *
     * @param string $key
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if ( ! $key) {
            return $this->data;
        }
        $target = $this->data;
        $keys = explode('.', $key);
        while (count($keys)) {
            $key = array_shift($keys);
            // Check if current level is object
            if (isset($target->$key)) {
                $target = (array) $target;
            }
            if ( ! isset($target[$key])) {
                break;
            }
            if (empty($keys)) {
                return $target[$key];
            }
            $target = &$target[$key];
        }
        return $default;
    }

    /**
     * Determine if key is set
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        $target = $this->data;
        $keys = explode('.', $key);
        while ($key = array_shift($keys)) {
            if (empty($keys)) {
                if ( ! isset($target[$key])) {
                    return false;
                }
                return true;
            }
            $target = &$target[$key];
        }
        return false;
    }

    /**
     * Merge data recursively
     *
     * @param mixed $keyOrData
     * @param mixed $dataOrCallable
     * @param $this
     */
    public function merge($keyOrDataOrCallable, $dataOrCallable = null)
    {
        $keyOrData = is_callable($keyOrDataOrCallable) ? $keyOrDataOrCallable($this->data) : $keyOrDataOrCallable;
        if (is_array($keyOrData)) {
            $this->data = $this->mergeRecursive($this->data, $keyOrData);
        } elseif (is_string($keyOrData)) {
            $target = &$this->data;
            $keys = explode('.', $keyOrData);
            while ($key = array_shift($keys)) {
                if ( ! isset($target[$key])) {
                    $target[$key] = array();
                }
                if (empty($keys)) {
                    $value = is_callable($dataOrCallable) ? $dataOrCallable($target[$key]) : $dataOrCallable;
                    if (is_array($target[$key])) {
                        $target[$key] = $this->mergeRecursive($target[$key], $value);
                    } else {
                        $target[$key] = $value;
                    }
                    break;
                }
                $target = &$target[$key];
            }
        }
        return $this;
    }

    /**
     * Set a value
     *
     * @param mixed $key
     * @param mixed $dataOrCallable
     * @param $this
     */
    public function set($key, $dataOrCallable = array())
    {
        $target = &$this->data;
        $keys = explode('.', $key);
        while ($key = array_shift($keys)) {
            if ( ! isset($target[$key])) {
                $target[$key] = array();
            }
            if (empty($keys)) {
                if (is_callable($dataOrCallable)) {
                    $target[$key] = $dataOrCallable($target[$key]);
                } else {
                    $target[$key] = $dataOrCallable;
                }
                break;
            }
            $target = &$target[$key];
        }
        return $this;
    }

    /**
     * Unset a value
     *
     * @param string $key
     * @return void
     */
    public function _unset($key)
    {
        $target = &$this->data;
        $keys = explode('.', $key);
        while ($key = array_shift($keys)) {
            if (empty($keys)) {
                unset($target[$key]);
            }
            if (isset($target[$key])) {
                $target = &$target[$key];
            } else {
                break;
            }
        }
        return $this;
    }

    /**
     * Recursively merge 2 arrays
     * On any level, if types differ, $from will replace $target
     * If both are type other than array, $from will replace $target
     * If both are array, string named key values will merge into $target,
     * while any numbered keys will be appended to $target. In both cases,
     * there will be no duplicates,
     * and numbered keys in $from will not be preserved
     *
     * @param array $target
     * @param mixed $from
     * @return mixed $result
     */
    protected function mergeRecursive(array $target, $from)
    {
        if (is_callable($from)) {
            $from = $from($target);
        }
        if (is_array($from)) {
            foreach ($from as $key => $value) {
                if (is_callable($value)) {
                    $value = $value($target[$key]);
                }
                if (is_string($key)) {
                    if (is_array($value)) {
                        $target[$key] = $this->mergeRecursive($target[$key], $value);
                    } else {
                        $target[$key] = $value;
                    }
                } elseif ( ! in_array($value, $target)) {
                    $target[] = $value;
                }
            }
            return $target;
        }
        return $from;
    }

}
