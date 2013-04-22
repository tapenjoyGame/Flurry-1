<?php
namespace MyLittle\Flurry;
use Exception;

/**
 * Flurry Class
 * 
 * This source file can be used to communicate with Flurry (www.flurry.com/)
 * You need to enable the API acces in your flurry account before using this class
 * ( Class uses JSON )
 * 
 * @author          Ekaterina Johnston <ekaterina.johnston@mylittleparis.com>
 * @version         1.0.0 (2013-04-22)
 * 
 */
class FlurryClient
{
    /**
     * List of Fluffy APIs
     * @var array
     */
    private $apis = array('appMetrics', 'appInfo', 'eventMetrics');

    /**
     * List of metrics for "appMetrics" API
     * @var array
     */
    private $appMetrics = array(
                    "ActiveUsers",
                    "ActiveUsersByWeek",
                    "ActiveUsersByMonth",
                    "NewUsers",
                    "MedianSessionLength",
                    "AvgSessionLength",
                    "Sessions",
                    "RetainedUsers",
                    "PageViews",
                    "AvgPageViewsPerSession"
                );
    /**
     * List of metrics for "appInfo" API
     * @var array
     */
    private $appInfo = array(
                    "getApplication",
                    "getAllApplications"
                );

    /**
     * List of metrics for "eventMetrics" API
     * @var array
     */
    private $eventMetrics = array(
                    "Summary",
                    "Event" 
                );
    
    /**
     * Flurry Api Access Code and Api Key (https://dev.flurry.com/)
     * API access needs to be enabled before using this class
     */
    private $apiAccessCode;
    private $apiKey;
    
    /**
     * Default Constructor
     * @param type $apiAccessCode
     * @param type $apiKey
     * @return type
     */
    public function __construct($apiAccessCode, $apiKey)
    {
        $this->apiAccessCode = $apiAccessCode;
        $this->apiKey = $apiKey;
    }
    
    /**
     * Resets the ApiKey
     * @param type $apiAccessCode
     * @param type $apiKey
     */
    public function connectToApi($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * Gets the apiKey
     * @return string
     */
    private function getApiKey()
    {
        return (string) $this->apiKey;
    }

    /**
     * Gets the apiAccessCode
     * @return string
     */
    private function getApiAccessCode()
    {
        return (string) $this->apiAccessCode;
    }
    
    /**
     * Makes a Flurry Calls
     * @param string $api           Name of APi to use
     * @param string $metric_name   Name of metric to use
     * @param string $startDate     YYYY-MM-DD format
     * @param string $endDate       YYYY-MM-DD format
     * @param string $eventName     Name of the Event to use (only applicable for )
     * @param string $country       Specifying a value of "ALL" or "all" for the COUNTRY will return a result which is broken down by countries
     * @param string $versionName   Name set by the developer for each version of the application. This can be found by logging into the Flurry website or contacting support.
     * @param string $groupBy       Changes the grouping of data into DAYS, WEEKS, or MONTHS. All metrics default to DAYS (except ActiveUsersByWeek and ActiveUsersByMonth)
     * @return type ??
     * 
     * @example $this->call('appMetrics', 'activeUsers', '2013-04-19', null, null, null, null, null)
     */
    private function call($api, $metric_name, $startDate, $endDate, $eventName, $country, $versionName, $groupBy)
    {
        // Formatting the date
        if (null == $endDate)
            $endDate = $startDate;
        if ((null != $endDate)&&(!is_string($startDate)))
            $startDate = $this->convertDateToString($startDate);
        if ((null != $endDate)&&(!is_string($endDate)))
            $endDate = $this->convertDateToString($endDate);
        
        //Configures parameters
        $parameters = array(
            // One for each Flurry account
            'apiAccessCode' =>$this->getApiAccessCode(), 
            // One for each application
            'apiKey' => $this->getApiKey(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'eventName' => $eventName,
            'country' => $country,
            'versionName' => $versionName,
            'groupBy' => $groupBy
            );

        //Generates the URL
        $url = "http://api.flurry.com/".$api."/".$metric_name."?".http_build_query($parameters);

        $config = array(
                'http' => array(
                    'header' => 'Accept: application/json',
                    'method' => 'GET',
                    'ignore_errors' =>  true,
                )
            );
        $stream = stream_context_create($config);
        
        try {
            $result = $this->getContents($url, $stream);
        } catch (Exception $e) {
            die ('Caught error : '.$e->getMessage());
        }

        return $result; 
    }

    /**
     * Tries to get file contents and json decode it
     * @return string  $stringdate
     */
    private function getContents($url, $stream) {
        $contents = file_get_contents($url, false, $stream);
        $result = json_decode($contents);
        if (isset($result->code)) {
            throw new Exception($result->code." - '".$result->message."'", 1);
        }
        return $result;
    }


    /**
     * Convers a DateTime object to YYYY-MM-DD formatted string
     * @param datetime $datetimeobject
     * @return string  $stringdate
     */
    private function convertDateToString($datetimeobject) {
        $stringdate = date_format($datetimeobject , "Y-m-d");
        return $stringdate;
    }
    
    /**
     * Helper function. Recursively converts an object to array
     * @param object $obj
     * @return array $arr
     */
    public function convertObjectToArray($obj) 
    {
        $arr = array();
        $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
            foreach ($arrObj as $key => $val) {
                    $val = (is_array($val) || is_object($val)) ? $this->convertObjectToArray($val) : $val;
                    $arr[$key] = $val;
            }
            return $arr;
    }
    
    /////////////////////
    ////  API Functions
    /////////////////////
    
    //////////  "appMetrics" API functions
    // Parameter order : ($startDate, $endDate, $country, $versionName, $groupBy)
    // No 'groupBy' parameter for 'getActiveUsersXXX' functions
    
    /**
     *  Total number of unique users who accessed the application per day
     */
    public function getActiveUsers($startDate, $endDate=null, $country=null, $versionName=null) {
        return $this->call('appMetrics', 'ActiveUsers', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy = null);
    }
    
    /**
     * Total number of unique users who accessed the application per week
     * Only returns data for dates which specify at least a complete calendar week
     * (Can't use 'groupBy' parameter. The data is grouped by WEEKS)
     */
    public function getActiveUsersByWeek($startDate, $endDate=null, $country=null, $versionName=null) {
        return $this->call('appMetrics', 'ActiveUsersByWeek', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy = null);
    }
    
    /**
     * Total number of unique users who accessed the application per week
     * Only returns data for dates which specify at least a complete calendar month
     * (Can't use 'groupBy' parameter. The data is grouped by MONTHS)
     */
    public function getActiveUsersByMonth($startDate, $endDate=null, $country=null, $versionName=null) {
        return $this->call('appMetrics', 'ActiveUsersByMonth', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy = null);
    }
    
    /**
     * Total number of unique users who used the application for the first time per day
     */
    public function getNewUsers($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'NewUsers', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Median length of a user session per day
     */
    public function getMedianSessionLength($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'MedianSessionLength', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Average length of a user session per day
     */
    public function getAvgSessionLength($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'AvgSessionLength', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * The total number of times users accessed the application per day
     */
    public function getSessions($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'Sessions', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Total number of users who remain active users of the application per day
     */
    public function getRetainedUsers($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'RetainedUsers', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Total number of page views per day
     */
    public function getPageViews($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'PageViews', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Average page views per session for each day
     */
    public function getAvgPageViewsPerSession($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'AvgPageViewsPerSession', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
  
    //////////  "appInfo" API functions
    /// No parameters

    /**
     * 	Information on a specific project
     */
    public function getApplicationInfo() {
        return $this->call('appInfo', 'getApplication', $startDate=null, $endDate=null, $eventName=null, $country=null, $versionName=null, $groupBy=null);
    }
    
    /**
     * 	Information on all projects under a specific company
     */
    public function getAllApplications() {
        return $this->call('appInfo', 'getAllApplications', $startDate=null, $endDate=null, $eventName=null, $country=null, $versionName=null, $groupBy=null);
    }
    
    //////////  "eventMetrics" API functions
    // (There is no guarantee of uniqueness of users for each period. For example, a unique user counted on day 1 could be counted again on day 2 if he uses the app on both days)
    // Parameter order : getEventMetricsSummary($startDate, $endDate, $versionName)
    // Parameter order : getEventMetrics($eventName, $startDate, $endDate, $versionName)

    /**
     * 	Returns a list of all events for the specified application with the following information for each
     * 
     *  Event Name              The name of the event
     *  Users Last Day          Total number of unique users for the last complete day
     *  Users Last Week         Total number of unique users for the last complete week
     *  Users Last Month	Total number of unique users for the last complete month
     *  Avg Users Last Day	The average of the number of unique users for each day in the interval
     *  Avg Users Last Week	The average of the number of unique users for each week in the interval
     *  Avg Users Last Month	The average of the number of unique users for each month in the interval
     *  Total Counts            Total number of time the event occurred
     *  Total Sessions          Total number of sessions
     */
    public function getEventMetricsSummary($startDate, $endDate=null, $versionName=null) {
        return $this->call('eventMetrics', 'Summary', $startDate, $endDate, $eventName=null, $country=null, $versionName=null, $groupBy=null);
    }
    
    /**
     *  The metrics returned are:
     * 
     *  Unique Users	Total number of unique users
     *  Total Sessions	Total number of sessions
     *  Total Count	Total number of time the event occurred
     *  Duration	Total   Duration of the event (Will be displayed only if the event is timed)
     *  Parameters	This will return a list of key/values. The key is the name of the parameter
     *  The values have the following metrics: name (the event name) and totalCount (the number of sessions)
     */
    public function getEventMetrics($eventName, $startDate, $endDate=null, $versionName=null) {
        return $this->call('eventMetrics', 'Event', $startDate, $endDate, $eventName, $country=null, $versionName=null, $groupBy=null);
    }
 
    //////////  Supplementary helper functions basen on "eventMetrics" API functions

    public function getEventList() {
        $list = array();
        $today = date("Y-m-d");
        $event_metrics_summary = $this->getEventMetricsSummary($today);
        sleep(1);
        $array = $event_metrics_summary->event;
        foreach ($array as $event_object) {
            $event_name = $this->convertObjectToArray($event_object)["@eventName"];
            array_push($list, $event_name);
        }
        return $list;
    }

    /**
     * Finds the array with a given parameter name in an event metric object
     * 
     */
    public function findParamInEventMetric($object, $paramName) {
        if (!is_array($object)) {
            $full_array = $this->convertObjectToArray($object);
        } else {
            $full_array = $object;
        }

        // Gets the correct part of the array
        $array = $full_array["parameters"]["key"];
        if (isset($array["value"]))
            $array = $array["value"];

        $param_array = array();
        foreach ($array as $key => $value) {
            // Gets the name
            isset($value["@name"]) ? $name = $value["@name"] : $name = $value;
            // Gets rid of "NUM | " part of name
            $name = substr(strstr($name, "|"), 2);
            if ($name == $paramName)
                $param_array = $value;
        }
        return (empty($param_array)) ? null : $param_array;
    }

    /**
     * Generates an array for parameter(s) with given events metrics
     * 
     * @param array $events Events to search the parameters for
     * @param array|string $param Parameter(s) to search the events for 
     * @return array
     */
    public function getEventsForParam($events, $param, $startDate, $endDate=null) {
        if (is_array($param)) {
            return $this->getEventsForMultipleParameters($events, $param, $startDate, $endDate);
        }
        else if (is_string($param)) {
            return $this->getEventsForSingleParameter($events, $param, $startDate, $endDate);
        }
    }

    /**
     * Generates an array of metrics for given events for a single given parameter
     * @return array
     */
    private function getEventsForSingleParameter($events, $parameter, $startDate, $endDate=null) {
        $return_array = array();
        foreach ($events as $event) {
            $event_metric_object = $this->getEventMetrics($event, $startDate, $endDate);
            sleep(1);
            $parameter_array = $this->findParamInEventMetric($event_metric_object, $parameter);
            $return_array[$event] = $parameter_array["@totalCount"];
        }
        return $return_array;
    }

    /**
     * Generates a multidimensionnal array of metrics for given events for each given parameter
     * @return array
     */
    private function getEventsForMultipleParameters($events, $parameters, $startDate, $endDate=null) {
        $return_array = array();
        foreach ($events as $event) {
            $event_metric_object = $this->getEventMetrics($event, $startDate, $endDate);
            sleep(1);
            foreach ($parameters as $parameter) {
                $parameter_array = $this->findParamInEventMetric($event_metric_object, $parameter);
                $return_array[$parameter][$event] = $parameter_array["@totalCount"];
            }

        }
        return $return_array;
    }
}