<?php


namespace medeirosinacio;

use ErrorException;

class ExecMultithread
{
	
	public int $maxProcesses;
	
	public string $projectRoot;
	
	public array $pendingProcesses = [];
	
	public array $completeProcesses = [];
	
	
	public function __construct($inMaxProcesses = 1, $projectRoot = '')
	{
		$this->maxProcesses = $inMaxProcesses;
		$this->projectRoot = $projectRoot;
		
		$this->loadPolicySecurity();
	}
	
	/**
	 * Ensure all the processes finish nicely
	 */
	public function __destruct()
	{
		$this->killAllProcesses();
	}
	
	/**
	 * Sets how many processes can be outstanding at once before we block and wait for one to
	 * finish before starting the next one
	 * @param int $inMaxProcesses
	 */
	public function setMaxProcesses($inMaxProcesses)
	{
		$this->maxProcesses = $inMaxProcesses;
	}
	
	/**
	 * Sets how project path
	 * @param $path
	 * @throws ErrorException
	 */
	public function setProjectRoot($path)
	{
		
		if (!is_dir($path)) {
			throw new ErrorException("The project root directory does not exist");
		}
		
		$this->projectRoot = $path;
	}
	
	/**
	 * Start a fetch from the $file process
	 * Return pid to process
	 * @param $file
	 * @param array $data = convert in json + md5 to argument
	 * @return bool|int  pid to process
	 * @throws ErrorException
	 */
	public function startProcess($file, $data = [])
	{
		if ($this->maxProcesses > 0) {
			$this->waitForOutstandingProcessesToDropBelow($this->maxProcesses);
		}
		
		$this->checkFleExist($file);
		
		$pid = exec("php " . $this->pathNormalize($file) . ".php " . $this->convertDataToArgument($data) . "> /dev/null 2>&1 & echo $!");
		
		$this->pendingProcesses[] = [
			'file' => $file,
			'data' => $data,
			'pid' => $pid ?? null,
			'status' => 'pending',
			'date' => date('Y-m-d H:i:s'),
		];
		
		$this->checkForCompletedProcesses();
		
		return (is_numeric($pid) ? (int)$pid : false);
	}
	
	/**
	 * @param null $pid
	 * @return array
	 */
	public function getProcessesInfo($pid = null)
	{
		$processes = array_merge($this->pendingProcesses, $this->completeProcesses);
		
		$onlyPids = array_column($processes, 'pid');
		
		if (!$pid) {
			return $processes;
		}
		
		$pidsToArrayKey = array_combine($onlyPids, $processes)[$pid];
		
		return $pidsToArrayKey[$pid] ?? [];
	}
	
	/**
	 * You *MUST* call this function at the end of your script. It waits for any running processes
	 * to complete, and calls their callback functions
	 */
	public function killAllProcesses()
	{
		foreach ($this->pendingProcesses as $key => $request) {
			if (is_numeric($request['pid']) && posix_getpgid($request['pid'])) {
				$this->killProcess($request['pid']);
			}
		}
	}
	
	/**
	 * @param int $pid
	 */
	private function killProcess($pid)
	{
		posix_kill($pid, 0);
	}
	
	/**
	 * Checks to see if any of the outstanding processes have finished
	 * @return void
	 */
	private function checkForCompletedProcesses()
	{
		foreach ($this->pendingProcesses as $key => $request) {
			if (is_numeric($request['pid']) && !posix_getpgid($request['pid'])) {
				$this->completeProcesses[] = array_merge($this->pendingProcesses[$key], [
					'status' => 'finished'
				]);
				unset($this->pendingProcesses[$key]);
			}
		}
	}
	
	/**
	 *  Blocks until there's less than the specified number of processes outstanding
	 * @param $max
	 */
	private function waitForOutstandingProcessesToDropBelow($max)
	{
		while (1) {
			$this->checkForCompletedProcesses();
			if (count($this->pendingProcesses) < $max) {
				break;
			}
			
			usleep(10000);
		}
	}
	
	
	private function loadPolicySecurity()
	{
		ini_set('safe_mode_allowed_env_vars ', 'PHP_');
	}
	
	/**
	 * @param $file
	 * @return bool
	 * @throws ErrorException
	 */
	private function checkFleExist($file)
	{
		if (is_dir($file . '.php') || !file_exists($file . '.php')) {
			throw new ErrorException("The file worker {$file} does not exist");
		}
		
		return true;
	}
	
	/**
	 * @param $path
	 * @return bool|string
	 */
	private function pathNormalize($path)
	{
		$path = $this->projectRoot . $path;
		
		$patterns = array('~/{2,}~', '~/(\./)+~', '~([^/\.]+/(?R)*\.{2,}/)~', '~\.\./~');
		$replacements = array('/', '/', '', '');
		
		return preg_replace($patterns, $replacements, $path);
		
	}
	
	/**
	 * @param $data
	 * @return string
	 */
	private function convertDataToArgument($data)
	{
		return base64_encode(json_encode($data));
	}
	
	/**
	 * @param $data
	 * @return mixed
	 */
	public static function convertData($data)
	{
		return json_decode(base64_decode($data));
	}
	
	/**
	 * @param $data
	 * @return mixed
	 */
	public function convertArgumentToData($data)
	{
		return self::convertData($data);
	}
	
}