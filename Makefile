SHELL := /bin/bash
.PHONY: clean box compile
.ONESHELL:

# Binaries
WGET_BIN := $(shell command -v wget)
TARGET ?= unbound-blacklist.phar

# Defaults
BOX_VERSION ?= 3.13.0

all :: compile

clean ::
	@rm -f $(TARGET)
	@rm -f box.phar

box ::
	@$(WGET_BIN) -q -O box.phar https://github.com/box-project/box/releases/download/$(BOX_VERSION)/box.phar || (echo "ERROR: Could not download box.phar !" && exit 1)
	@chmod +x box.phar

compile :: clean box
	box compile