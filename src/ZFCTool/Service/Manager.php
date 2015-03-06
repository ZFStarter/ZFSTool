<?php
/**
 * User: dev
 * Date: 19.02.15 17:48
 */

namespace ZFCTool\Service;

use ZFCTool\Exception\ZFCToolException;

class Manager
{
    /**
     * Variable contents options
     *
     * @var array
     */
    protected $options = array(
        // Path to project directory
        'projectDirectoryPath' => null,
        // Path to modules directory
        'modulesDirectoryPath' => null,
    );

    /**
     * Method return application directory path
     *
     * @throws ZFCToolException
     * @return string
     */
    public function getProjectDirectoryPath()
    {
        if (null == $this->options['projectDirectoryPath']) {
            throw new ZFCToolException('Project directory path undefined.');
        }

        return $this->options['projectDirectoryPath'];
    }

    /**
     * Method set application directory path
     *
     * @param  string $value
     * @return MigrationManager
     */
    public function setProjectDirectoryPath($value)
    {
        $this->options['projectDirectoryPath'] = $value;
        return $this;
    }

    /**
     * Get modules directory path(s)
     *
     * @throws ZFCToolException
     * @return array|string
     */
    public function getModulesDirectoryPath()
    {
        if (null == $this->options['modulesDirectoryPath']) {
            throw new ZFCToolException('Modules directory path undefined.');
        }

        return $this->options['modulesDirectoryPath'];
    }

    /**
     * Set modules directory path
     *
     * @param mixed $value
     * @return MigrationManager
     */
    public function setModulesDirectoryPath($value)
    {
        $this->options['modulesDirectoryPath'] = $value;
        return $this;
    }

    /**
     * Method returns path to $dirName directory
     *
     * @param string $dirName
     * @param string $module Module name
     * @return string
     * @throws ZFCToolException
     */
    protected function getDirectoryPath($dirName, $module = null)
    {
        if (null == $module) {
            $path = $this->getProjectDirectoryPath();
            $path .= '/' . $dirName;

            $this->preparePath($path);

            return $path;
        }

        $modulePaths = $this->getModulesDirectoryPath();

        if (!is_array($modulePaths)) {
            $modulePath = $modulePaths . '/' . $module;

            if (!file_exists($modulePath)) {
                throw new ZFCToolException("Module `$module` not exists.");
            }

            $path = $modulePath . '/' . $dirName;
            $this->preparePath($path);

            return $path;
        }

        foreach ($modulePaths as $modulePath) {
            $path = $modulePath . '/' . $module;

            if (file_exists($path)) {
                $path .= '/' . $dirName;
                $this->preparePath($path);

                return $path;
            }
        }

        throw new ZFCToolException('Module `' . $module . '` not exists.');
    }

    /**
     * Method returns path(s) to $dirName directories
     *
     * @param string $dirName
     * @param null $module Module name
     * @param bool $scanModuleDirectories Looking for $dirName in site root dir
     * @return array
     * @throws ZFCToolException
     */
    protected function getDirectoryPaths($dirName, $module = null, $scanModuleDirectories = false)
    {
        $modulePaths = $this->getModulesDirectoryPath();

        if (!is_array($modulePaths)) {
            $modulePaths = array($modulePaths);
        }

        $paths = array();

        if (null !== $module) {
            foreach ($modulePaths as $path) {
                $modulePath = $path . '/' . $module;

                if (file_exists($modulePath)) {
                    $paths[$module] = $modulePath . '/' . $dirName;
                }
            }

            if (empty($paths)) {
                throw new ZFCToolException("Module `$module` not exists.");
            }

            return $paths;
        }

        $path = $this->getProjectDirectoryPath();
        $path .= '/' . $dirName;
        $this->preparePath($path);

        $paths[''] = $path;

        if ($scanModuleDirectories) {
            foreach ($modulePaths as $path) {
                if (!is_dir($path)) {
                    continue;
                }

                $filesDirty = array_diff(scandir($path), array('.', '..'));

                foreach ($filesDirty as $dir) {
                    $modulePath = $path.'/'.$dir;

                    if (is_dir($modulePath)) {
                        $migrationPath = $modulePath . '/' . $dirName;

                        if (file_exists($migrationPath)) {
                            $paths[$dir] = $migrationPath;
                        }
                    }
                }
            }
        }

        return $paths;
    }

    /**
     * Method returns array of exists in filesystem files
     *
     * @param string $ext File extension
     * @param array  $modulePaths
     * @return array
     */
    protected function getExistsFiles($ext, $modulePaths)
    {
        $files = array();

        foreach ($modulePaths as $moduleName => $modulePath) {
            $filesDirty = scandir($modulePath);

            // foreach loop for $filesDirty array
            foreach ($filesDirty as $file) {
                if (preg_match('/(\d{8}_\d{6}_\d{2}[_]*[A-z0-9]*)\.'.$ext.'$/', $file, $match)) {
                    $files[$moduleName][] = $match[1];
                }
            }

        }

        $this->sortArray($files);

        return $files;
    }

    /**
     * execute array from string
     * @param $str
     * @return array
     */
    protected function strToArray($str)
    {
        if (!empty($str)) {
            if (strpos($str, ',')) {
                return explode(',', $str);
            }
            return array($str);
        } else {
            return array();
        }
    }

    /**
     * Method prepare path (create not existing dirs)
     *
     * @param string $path
     */
    protected function preparePath($path)
    {
        if (!is_dir($path)) {
            $this->preparePath(dirname($path));
            mkdir($path, 0777);
        }
    }

    /**
     * Sorting array by order
     *
     * @param array $array
     * @param string $order
     */
    protected function sortArray(&$array, $order = 'ASC')
    {
        if (!count($array)) {
            return;
        }

        if (count($array) == 1) {
            reset($array);
            if ($order == 'ASC') {
                sort($array[key($array)]);
            } else {
                rsort($array[key($array)]);
            }
        } else {
            uasort($array, function(&$a, &$b) use ($order) {
                if ($order == 'ASC') {
                    sort($a);
                    sort($b);
                } else {
                    rsort($a);
                    rsort($b);
                }

                return (reset($a) < reset($b)) ? 1 : (end($a) > end($b)) ? -1 : 0;
            });
        }
    }

    /**
     * Return difference between two arrays
     *
     * @param array $aArray1
     * @param array $aArray2
     * @return array
     */
    protected function arrayDiff($aArray1, $aArray2)
    {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    }

    /**
     * Get key from array
     *
     * @param string $needle
     * @param array $array
     * @return mixed
     */
    protected function getArrayKey($needle, $array)
    {
        foreach ($array as $key => $value) {
            foreach ($value as $item) {
                if ($item === $needle) {
                    return $key;
                }
            }
        }

        return false;
    }

    /**
     * Looking if array contain value
     *
     * @param string $needle
     * @param array $haystack
     * @param bool $strict
     * @return bool
     */
    protected function valueInArray($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) ||
                (is_array($item) && $this->valueInArray($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge arrays
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    protected function arraysMerge(array $array1, array $array2)
    {
        $merged = array_merge_recursive($array1, $array2);

        foreach ($merged as $key => &$value) {
            $value = array_unique($value);
        }

        return $merged;
    }
}
