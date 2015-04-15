# phoronix-python-results
Print the result of Phoronix Test Suite benchmarks in a python readable format.

The output of this module can be parsed by python using `ast.literal_eval(output)`.

# Install
## Option 1
Copy `python_results.php` to `~/.phoronix-test-suites/modules/`
## Option 2
Use installation file included: `./install.sh`

# Usage
## Basic Test
`phoronix-test-suite python_results.run test1 test2 test3`

## Tests With Parameters
`PRESET_OPTIONS='stream.run-type=Add' phoronix-test-suite python_results.run stream`
