<?php

namespace diversen;

class bgJob {
    
    /**
     * Execute a background job. 
     * @param string $cmd
     * @param string $outputfile
     * @param string $pidfile
     * @return boolean $res
     */
    public function execute ($cmd, $outputfile, $pidfile) {
        $res = exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));
        if (!$res) {
            return true;
        }
        return false;
    }
    
    /**
     * Check is a process is running
     * @param int $pid
     * @return boolean $res 
     */
    function isRunning($pid) {
        try {
            $result = shell_exec(sprintf("ps %d", $pid));
            if (count(preg_split("/\n/", $result)) > 2) {
                return true;
            }
        } catch (Exception $e) {
            
        }

        return false;
    }

}