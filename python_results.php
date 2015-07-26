<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
	python_results.php

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class python_results extends pts_module_interface
{
	const module_name = 'Python Results Module';
	const module_version = '1.0.0';
	const module_description = "Display's python readable results";
	const module_author = 'Liran Funaro';
	const tab = '    ';
	const result_list_wrapper = '########## RESULT-LIST ##########';

	public static function module_info()
	{
		return 'Thid module allows to run a batch of tests and the output to stdout will be readable by pthon eval() command.';
	}
	public static function user_commands()
	{
		return array('run' => 'run_command');
	}
	
	//
	// User Commands
	//

	public static function run_command($to_run)
	{
		self::install_tests_to_run($to_run);
		$results_list = self::run_tests($to_run);
		
		echo PHP_EOL . python_results::result_list_wrapper . PHP_EOL . $results_list . PHP_EOL . python_results::result_list_wrapper . PHP_EOL;
	}
	
	public static function run_tests($to_run)
	{
	    $flags = pts_c::auto_mode;
		
		// To specify test options externally from an environment variable
		// i.e. PRESET_OPTIONS='stream.run-type=Add' ./phoronix-test-suite python_results.run stream
		if(pts_client::read_env('PRESET_OPTIONS') == false) {
		    $flags |= pts_c::batch_mode;
		}
		
	    // Do the actual running
		if(timed_test_run_manager::initial_checks($to_run, $flags))
		{
			$test_run_manager = new timed_test_run_manager($flags);

			if($test_run_manager->load_tests_to_run($to_run))
  			{
				$test_run_manager->pre_execution_process();
				$test_run_manager->call_test_runs();
				$test_run_manager->post_execution_process();
				
				return self::get_test_results($test_run_manager->get_tests_to_run());
			}
		}
	}
	
	public static function get_test_results($test_result_list)
	{
	    $res = "";
	    foreach($test_result_list as $test_result) {
			$res .= "{" . PHP_EOL;
			
		    $res .= self::get_dict_item("result",$test_result->get_result());
		    $res .= self::get_dict_item("scale",$test_result->test_profile->get_result_scale());
		    $res .= self::get_dict_item("title",$test_result->test_profile->get_title());
		    $res .= self::get_dict_item("arguments",$test_result->get_arguments());
		    $res .= self::get_dict_item("arguments-description",$test_result->get_arguments_description());
		    $res .= self::get_dict_item("estimated-run-time",intval($test_result->test_profile->get_estimated_run_time()));
		    
		    if(is_object($test_result->test_result_buffer)) {
		        // More timing information
		        $values = $test_result->test_result_buffer->get_values();
		        $values_count = count($values);
		        
		        if($values_count > 0) {
		            $res .= self::get_dict_item("all-results",$values);				    
		            $res .= self::get_dict_item("avg",array_sum($values) / $values_count);
		            $res .= self::get_dict_item("min",min($values));
		            $res .= self::get_dict_item("max",max($values));
		            
		            $std_div = 0;
	                if($values_count > 1) {
    	                $std_div = pts_math::percent_standard_deviation($values);
	                }
	                $res .= self::get_dict_item("std-dev",$std_div);
	            }
	        }
	        
		    $res .= "}," . PHP_EOL;
	    }
	    
	    return $res;
	}
	
	public static function get_dict_item($key,$value)
	{
	    return python_results::tab . self::get_dict_value($key) . ": " . self::get_dict_value($value) . "," . PHP_EOL;
	}
	
	public static function get_dict_value($value)
	{
	    if(is_string($value)) {
	        return "'" . $value . "'";
	    } else if(is_array($value)) {
	        $res = "";
	        foreach($value as $val) {
	            $res .= self::get_dict_value($val) . ",";
	        }
	        return "(" . $res . ")";
	    } else {
	        return $value;
	    }
	}
	
	// This is a copy with slight modification of pts_tes_run_manager::cleanup_tests_to_run()
	public static function install_tests_to_run($to_run)
	{
	    $to_run_objects = pts_types::identifiers_to_objects($to_run);
	    
		$skip_tests = ($e = pts_client::read_env('SKIP_TESTS')) ? pts_strings::comma_explode($e) : false;
		$tests_verified = array();
		$tests_missing = array();

		foreach($to_run_objects as &$run_object)
		{
			if($skip_tests && (in_array($run_object->get_identifier(false), $skip_tests) || ($run_object instanceof pts_test_profile && in_array($run_object->get_identifier_base_name(), $skip_tests))))
			{
				echo 'Skipping: ' . $run_object->get_identifier() . PHP_EOL;
				continue;
			}
			else if($run_object instanceof pts_test_profile)
			{
				if($run_object->get_title() == null)
				{
					echo 'Not A Test: ' . $run_object . PHP_EOL;
					continue;
				}
				else
				{
					if($run_object->is_supported(false) == false)
					{
						continue;
					}
					if($run_object->is_test_installed() == false)
					{
						// Check to see if older version of test is currently installed
						// TODO: show change-log between installed versions and upstream
						array_push($tests_missing, $run_object);
						continue;
					}
				}
			}
			else if($run_object instanceof pts_result_file)
			{
				$num_installed = 0;
				foreach($run_object->get_contained_test_profiles() as $test_profile)
				{
					if($test_profile == null || $test_profile->get_identifier() == null || $test_profile->is_supported(false) == false)
					{
						continue;
					}
					else if($test_profile->is_test_installed() == false)
					{
						array_push($tests_missing, $test_profile);
					}
					else
					{
						$num_installed++;
					}
				}

				if($num_installed == 0)
				{
					continue;
				}
			}
			else if($run_object instanceof pts_test_suite || $run_object instanceof pts_virtual_test_suite)
			{
				if($run_object->is_core_version_supported() == false)
				{
					echo $run_object->get_title() . ' is a suite not supported by this version of the Phoronix Test Suite.' . PHP_EOL;
					continue;
				}

				$num_installed = 0;

				foreach($run_object->get_contained_test_profiles() as $test_profile)
				{
					if($test_profile == null || $test_profile->get_identifier() == null || $test_profile->is_supported(false) == false)
					{
						continue;
					}

					if($test_profile->is_test_installed() == false)
					{
						array_push($tests_missing, $test_profile);
					}
					else
					{
						$num_installed++;
					}
				}

				if($num_installed == 0)
				{
					continue;
				}
			}
			else
			{
				echo 'Not Recognized: ' . $run_object . PHP_EOL;
				continue;
			}

			array_push($tests_verified, $run_object);
		}

		$to_run_objects = $tests_verified;

		if(count($tests_missing) > 0)
		{
			$tests_missing = array_unique($tests_missing);

			$message = PHP_EOL . PHP_EOL . 'Some tests are not installed:' . PHP_EOL;
			$message .= pts_user_io::display_text_list($tests_missing);
			echo $message;

			pts_test_installer::standard_install($tests_missing, pts_c::force_install);
		}
	}

	//
	// General Functions
	//

	public static function __startup()
	{
		//echo PHP_EOL . 'The Phoronix Test Suite is starting up!' . PHP_EOL . 'Called: __startup()' . PHP_EOL;
	}
	public static function __shutdown()
	{
		//echo PHP_EOL . 'The Phoronix Test Suite is done running.' . PHP_EOL . 'Called: __shutdown()' . PHP_EOL;
	}

	//
	// Installation Functions
	//

	public static function __pre_install_process()
	{
		//echo PHP_EOL . 'Getting ready to check for test(s) that need installing...' . PHP_EOL . 'Called: __pre_install_process()' . PHP_EOL;
	}
	public static function __pre_test_download()
	{
		//echo PHP_EOL . 'Getting ready to download files for a test!' . PHP_EOL . 'Called: __pre_test_download()' . PHP_EOL;
	}
	public static function __interim_test_download()
	{
		//echo PHP_EOL . 'Just finished downloading a file for a test.' . PHP_EOL . 'Called: __interim_test_download()' . PHP_EOL;
	}
	public static function __post_test_download()
	{
		//echo PHP_EOL . 'Just finished the download process for a test.' . PHP_EOL . 'Called: __post_test_download()' . PHP_EOL;
	}
	public static function __pre_test_install()
	{
		//echo PHP_EOL . 'Getting ready to actually install a test!' . PHP_EOL . 'Called: __pre_test_install()' . PHP_EOL;
	}
	public static function __post_test_install()
	{
		//echo PHP_EOL . 'Just finished installing a test, is there anything to do?' . PHP_EOL . 'Called: __post_test_install()' . PHP_EOL;
	}
	public static function __post_install_process()
	{
		//echo PHP_EOL . 'We\'re all done installing any needed tests. Anything to process?' . PHP_EOL . 'Called: __post_install_process()' . PHP_EOL;
	}

	//
	// Run Functions
	//

	public static function __pre_run_process()
	{
		//echo PHP_EOL . 'We\'re about to start the actual testing process.' . PHP_EOL . 'Called: __pre_run_process()' . PHP_EOL;
	}
	public static function __pre_test_run()
	{
		//echo PHP_EOL . 'We\'re about to run a test! Any pre-run processing?' . PHP_EOL . 'Called: __pre_test_run()' . PHP_EOL;
	}
	public static function __interim_test_run()
	{
		//echo PHP_EOL . 'This test is being run multiple times for accuracy. Anything to do between tests?' . PHP_EOL . 'Called: __interim_test_run()' . PHP_EOL;
	}
	public static function __post_test_run()
	{
		//echo PHP_EOL . 'We\'re all done running this specific test.' . PHP_EOL . 'Called: __post_test_run()' . PHP_EOL;
	}
	public static function __post_run_process()
	{
		//echo PHP_EOL . 'We\'re all done with the testing for now.' . PHP_EOL . 'Called: __post_run_process()' . PHP_EOL;
	}
}

?>
