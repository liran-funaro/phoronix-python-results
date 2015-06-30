#!/usr/bin/env python
'''
Update a phoronix-test-suite user-config.xml file to load specific modules
@author Liran Funaro <fonaro@cs.technion.ac.il>
'''
import xml.etree.ElementTree as et
import argparse

def load_xml_file(filename):
	with open(filename, 'r') as f:
		return et.parse(f)

def get_modules_element(xml_element):
	return xml_element.find(".//Options/Modules/LoadModules")

def add_modules_to_user_config(filename, modules = []):
	xml_element = load_xml_file(filename)
	modules_element = get_modules_element(xml_element)

	current_modules_str = modules_element.text
	current_modules = [ module.strip() for module in current_modules_str.split(",") if module]
	
	for module in modules:
		if module not in current_modules:
			current_modules.append(module)

	modules_element.text = ", ".join(current_modules)

	xml_element.write(filename)

if __name__ == "__main__":
	parser = argparse.ArgumentParser()
	parser.add_argument('-f','--xml-file', required=True)
	parser.add_argument('-m','--module', required=True, action='append')
	args = parser.parse_args()

	add_modules_to_user_config(args.xml_file, args.module)