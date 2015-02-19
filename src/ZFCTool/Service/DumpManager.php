<?php
/**
 * User: naxel
 * Date: 06.03.14 11:19
 */

namespace ZFCTool\Service;

use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZFCTool\Exception\ZFCToolException;
use ZFCTool\Exception\EmptyDumpException;
use ZFCTool\Exception\DumpNotFound;

class DumpManager
{
    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $db;

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
        // Dump directory name
        'dumpsDirectoryName' => 'dumps'
    );


    /** @var  ServiceLocatorInterface */
    protected $serviceLocator;


    /**
     * @param $serviceLocator
     * @throws ZFCToolException
     */
    public function __construct($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        $config = $this->serviceLocator->get('Config');

        $this->options = array_merge($this->options, $config['ZFCTool']['migrations']);

        /** @var $db Adapter */
        $this->db = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');
    }


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
     * Get dumps directory name
     *
     * @return string
     * @throws ZFCToolException
     */
    public function getDumpsDirectoryName()
    {
        if (null == $this->options['dumpsDirectoryName']) {
            throw new ZFCToolException('Dumps directory name undefined.');
        }

        return $this->options['dumpsDirectoryName'];
    }


    /**
     * Set dumps directory name
     *
     * @param string $name
     */
    public function setDumpsDirectoryName($name)
    {
        $this->options['dumpsDirectoryName'] = $name;
    }


    /**
     * Method returns path to dumps directory
     *
     * @param string $module Module name
     * @return string
     * @throws ZFCToolException
     */
    public function getDumpsDirectoryPath($module = null)
    {
        if (null == $module) {
            $path = $this->getProjectDirectoryPath();
            $path .= '/' . $this->getDumpsDirectoryName();

            $this->preparePath($path);

            return $path;
        }

        $modulePaths = $this->getModulesDirectoryPath();

        if (!is_array($modulePaths)) {
            $modulePath = $modulePaths . '/' . $module;

            if (!file_exists($modulePath)) {
                throw new ZFCToolException("Module `$module` not exists.");
            }

            $path = $modulePath . '/' . $this->getDumpsDirectoryName();
            $this->preparePath($path);

            return $path;
        }

        foreach ($modulePaths as $modulePath) {
            $path = $modulePath . '/' . $module;

            if (file_exists($path)) {
                $path .= '/' . $this->getDumpsDirectoryName();
                $this->preparePath($path);

                return $path;
            }
        }

        throw new ZFCToolException('Module `' . $module . '` not exists.');
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
     * Method create dump of database
     *
     * @param null $module
     * @param string $name
     * @param string $whitelist
     * @param string $blacklist
     * @return string
     * @throws ZFCToolException
     */
    public function create($module = null, $name = '', $whitelist = "", $blacklist = "")
    {

        $database = new Database($this->db, $this->getOptions($whitelist, $blacklist));

        if ($dump = $database->getDump()) {
            $path = $this->getDumpsDirectoryPath($module);

            if (!$name) {
                list(, $mSec) = explode(".", microtime(true));
                $name = date('Ymd_His_') . substr($mSec, 0, 2) . '.sql';
            }

            file_put_contents($path . DIRECTORY_SEPARATOR . $name, $dump);

            return $name;

        } else {
            throw new EmptyDumpException("Can not get database dump!");
        }
    }


    /**
     * Import dump in database
     *
     * @param $name
     * @param null $module
     * @return StatementInterface|\Zend\Db\ResultSet\ResultSet
     * @throws DumpNotFound
     */
    public function import($name, $module = null)
    {
        $path = $this->getDumpsDirectoryPath($module);

        if (is_file($path . DIRECTORY_SEPARATOR . $name)) {
            $dump = file_get_contents($path . DIRECTORY_SEPARATOR . $name);

            return $this->db->query($dump, Adapter::QUERY_MODE_EXECUTE);

        } else {
            throw new DumpNotFound("Dump file not found!");
        }
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
     * get options for DB
     * @param string $whitelist
     * @param string $blacklist
     * @return array
     */
    protected function getOptions($whitelist = "", $blacklist = "")
    {
        $blkListedTables = array();
        $blkListedTables = array_merge($blkListedTables, $this->strToArray($blacklist));

        $whtListedTables = array();
        $whtListedTables = array_merge($whtListedTables, $this->strToArray($whitelist));

        $options = array();

        if (sizeof($whtListedTables) > 0) {
            $options['blacklist'] = $blkListedTables;
        }

        if (sizeof($whtListedTables) > 0) {
            $options['whitelist'] = $whtListedTables;
        }

        $options['loaddata'] = true;

        return $options;
    }
}
