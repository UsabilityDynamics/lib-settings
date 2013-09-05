REPORTER = list
JSON_FILE = static/all.json
HTML_FILE = static/coverage.html

test-all:
	clean
	document
	lib-cov
	test-code

document:
	yuidoc -q --configfile static/yuidoc.json

test-code:
	@NODE_ENV=test mocha \
	--timeout 200 \
	--ui exports \
	--reporter $(REPORTER) \
	test/*.js

test-cov: 
	lib-cov
	@APP_COVERAGE=1 $(MAKE) test \
	REPORTER=html-cov > $(HTML_FILE)

lib-cov:
	jscoverage lib static/lib-cov

clean:
	rm -fr static/codex
	rm -fr static/lib-cov
	rm -fr components
	rm -fr ux/build
