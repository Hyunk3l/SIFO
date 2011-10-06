<?php

/* VisualPHPUnit
 *
 * Copyright (c) 2011, Nick Sinopoli <nsinopoli@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Nick Sinopoli nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

    // AJAX calls
    if ( isset($_GET['dir']) ) {
        if ( !file_exists($_GET['dir']) ) {
            if ( $_GET['type'] == 'dir' ) {
                echo 'Directory does not exist!';
            } else {
                echo 'File does not exist!';
            }
        } elseif ( !is_writable($_GET['dir']) ) {
            if ( $_GET['type'] == 'dir' ) {
                echo 'Directory is not writable! (Check permissions.)';
            } else {
                echo 'File is not writable! (Check permissions.)';
            }
        } else {
            echo 'OK';
        }
        exit;
    }

	require 'config.php';
	
	define( 'ROOT_PATH', realpath( dirname( __FILE__ ) . '/../../..' ) );
	
	require_once ROOT_PATH . '/instances/Bootstrap.php';

	require_once ROOT_PATH . '/libs/SEOframework/Exceptions.php';
	require_once ROOT_PATH . '/libs/SEOframework/Registry.php';
	require_once ROOT_PATH . '/libs/SEOframework/Filter.php';
	require_once ROOT_PATH . '/libs/SEOframework/Config.php';
	require_once ROOT_PATH . '/libs/SEOframework/Domains.php';
	require_once ROOT_PATH . '/libs/SEOframework/Urls.php';
	require_once ROOT_PATH . '/libs/SEOframework/Router.php';
	require_once ROOT_PATH . '/libs/SEOframework/Database.php';
	require_once ROOT_PATH . '/libs/SEOframework/Controller.php';
	require_once ROOT_PATH . '/libs/SEOframework/Model.php';
	require_once ROOT_PATH . '/libs/SEOframework/View.php';
	require_once ROOT_PATH . '/libs/SEOframework/I18N.php';
	require_once ROOT_PATH . '/libs/SEOframework/Crypt.php';
	require_once ROOT_PATH . '/libs/SEOframework/Cookie.php';
	require_once ROOT_PATH . '/libs/SEOframework/Session.php';
	require_once ROOT_PATH . '/libs/SEOframework/Cache.php';
	require_once ROOT_PATH . '/libs/SEOframework/FlashMessages.php';
	require_once ROOT_PATH . '/libs/SEOframework/Mail.php';
	require_once ROOT_PATH . '/libs/SEOframework/Benchmark.php';
	require_once ROOT_PATH . '/libs/SEOframework/Client.php';
	
    if ( empty($_POST) ) {
        $results = array();
        $handler = opendir(SNAPSHOT_DIRECTORY);
        while ( $file = readdir($handler) ) {
            if ( $file != "." && $file != ".." ) {
                $results[] = $file;
            }
        }
        closedir($handler);
        arsort($results);

        include 'ui/index.html';
        exit; 
    }

    if ( $_POST['view_snapshot'] == 1 ) {
        $dir = realpath(SNAPSHOT_DIRECTORY) . '/';
        $snapshot = realpath($dir . trim(strval(filter_var($_POST['select_snapshot'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES))));
        include $snapshot;
        exit;
    }

    // Sanitize all the $_POST data
    $create_snapshots = (boolean) filter_var($_POST['create_snapshots'], FILTER_SANITIZE_NUMBER_INT);
    $snapshot_directory = trim(strval(filter_var($_POST['snapshot_directory'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
    $sandbox_errors = (boolean) filter_var($_POST['sandbox_errors'], FILTER_SANITIZE_NUMBER_INT);
    $sandbox_filename = trim(strval(filter_var($_POST['sandbox_filename'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
    if ( isset($_POST['sandbox_ignore']) ) {
        $sandbox_ignore = array();
        foreach ( $_POST['sandbox_ignore'] as $ignore ) {
            $sandbox_ignore[] = trim(strval(filter_var($ignore, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
        }
        $sandbox_ignore = implode('|', $sandbox_ignore);
    } else {
        $sandbox_ignore = '';
    }
    $test_files = trim(strval(filter_var($_POST['test_files'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
    $tests = explode('|', $test_files); 

    require 'VPU.php';
	require 'CoverageAnalysis.php';

    ob_start(); 

    $vpu = new VPU();

    if ( $sandbox_errors ) {
        set_error_handler(array($vpu, 'handle_errors'));
    }

    $results = $vpu->run($tests);

    include 'ui/header.html';
    echo $vpu->to_HTML($results['tests'], $sandbox_errors);

	$coverage_files = CoverageAnalysis::getFiles();
	foreach ( $coverage_files as $file )
	{
		$file_contents = file( $file );

		$lines_executed = array();
		$lines_executable = array();
		foreach( $results['coverage'][$file] as $key => $val )
		{
			if ( $val > 0 )
			{
				$lines_executed[] = $key;
			}
			else
			{
				$lines_executable[] = $key;
			}
		}

		foreach ( $file_contents as $line_number => $line )
		{
			$used = '';
			if ( in_array( $line_number + 1, $lines_executable ) )
			{
				$used = '0';
			}
			elseif ( in_array( $line_number + 1, $lines_executed ) )
			{
				$used = '1';
			}
			echo $used . highlight_string( $line, true );
		}
	}
	
    include 'ui/footer.html';

    if ( $create_snapshots ) {
        $snapshot = ob_get_contents(); 
        $vpu->create_snapshot($snapshot, $snapshot_directory);
    }

?>
