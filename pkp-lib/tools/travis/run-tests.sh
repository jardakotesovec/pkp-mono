#!/bin/bash

# @file tools/travis/run-tests.sh
#
# Copyright (c) 2014-2021 Simon Fraser University
# Copyright (c) 2010-2021 John Willinsky
# Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
#
# Script to run data build, unit, and integration tests.
#

set -xe

# Run the data build suite (integration tests).
$(npm bin)/cypress run --headless --browser chrome --config integrationFolder=cypress/tests/data

# Dump the database and files before continuing. Tests may restore this to reset the
# environment.
./lib/pkp/tools/travis/dump-database.sh
tar czf ${FILESDUMP} ${FILESDIR}

# Run the pkp-lib integration tests.
$(npm bin)/cypress run --headless --browser chrome --config integrationFolder=lib/pkp/cypress/tests/integration
if [ -d "cypress/tests/integration" ]; then
	# If application integration tests are provided, run them.
	$(npm bin)/cypress run --headless --browser chrome --config integrationFolder=cypress/tests/integration
fi

# Run the unit tests.
./lib/pkp/tools/runAllTests.sh -CcPpd
