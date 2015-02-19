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

class DumpManager extends Manager
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
        return $this->getDirectoryPath($this->getDumpsDirectoryName(), $module);
    }


    /**
     * Method returns path(s) to dump directories
     *
     * @param null $module Module name
     * @param bool $scanModuleDirectories Looking for dumps in site root dir
     * @return array
     * @throws ZFCToolException
     */
    public function getDumpsDirectoryPaths($module = null, $scanModuleDirectories = false)
    {
        return $this->getDirectoryPaths($this->getDumpsDirectoryName(), $module, $scanModuleDirectories);
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

    /**
     * Method returns array of exists in filesystem dump
     *
     * @param string $module Module name
     * @param bool   $scanModuleDirectories Looking for dumps in site root dir
     * @return array
     */
    public function getExistsDumps($module = null, $scanModuleDirectories = false)
    {
        $modulePaths = $this->getDumpsDirectoryPaths($module, $scanModuleDirectories);

        return $this->getExistsFiles('sql', $modulePaths);
    }
}
