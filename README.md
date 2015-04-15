# phoronix-python-results
Print the result of Phoronix Test Suite benchmarks in a python readable format (using ast.literal_eval())

# Install
Copy python_results.php to ~/.phoronix-test-suites/modules/
Or use installation file included: ./install.sh

# Usage
phoronix-test-suite python_results.run test1 test2 test3

If you need to give the tests parameters:
PRESET_OPTIONS='stream.run-type=Add' phoronix-test-suite python_results.run stream