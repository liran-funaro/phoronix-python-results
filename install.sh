#!/bin/sh
echo "Copying module to the users Phoronix Test Suite modules folder..."
cp -f -v ./*.php ~/.phoronix-test-suite/modules/

echo "Adding needed modules to user-config.xml..."
./add_loaded_modules.py -f ~/.phoronix-test-suite/user-config.xml -m timed_test_execution -m timed_test_run_manager
