# inanepain/dumper
# version: $Id$
# date: $Date$

#***********************************************
# readme: example
# just readme clean;echo; sleep 2; just readme markdown
#***********************************************

set shell := ["zsh", "-cu"]
set positional-arguments

project := "inane\\dumper"

# list recipes
_default:
    @echo "{{project}}:"
    @just --list --list-heading ''

#*********************************************
#### DOCUMENTATION: README
##############################################
# build: 1 - reduced adoc file
@_readme-reduce:
	echo "\tbuild: reduced"
	asciidoctor-reducer -o README.adoc doc/readme/index.adoc

# build: 2 - pandoc xml file
@_readme-pandoc:
	echo "\tbuild: pandoc"
	asciidoctor -b docbook README.adoc

# build: 3 - markdown file
@_readme-markdown:
	echo "\tbuild: markdown"
	pandoc -f docbook -t markdown_strict README.xml -o README.md

# clean: readme files
@_readme-clean:
	echo "\tbuild: clean..."
	rm -vf README.{adoc,xml,md}
	echo "\tbuild: clean: done"

# build README files from doc/readme/index.adoc (targets build required files if missing): clean, reduce, pandoc, markdown*
readme target="markdown":
	#!/usr/bin/env zsh
	[[ ! "{{target}}" = *"-v" ]] && echo "building: readme: {{target}}"

	if [[ "{{target}}" = "clean" ]]; then just _readme-clean
	elif [[ "{{target}}" = "reduce"* ]]; then
		if [[ -f doc/readme/index.adoc ]]; then just _readme-reduce; else echo "\tbuild: warn: missing: doc/readme/index.adoc (reduce)"; fi
	elif [[ "{{target}}" = "pandoc"* ]]; then
		if [[ ! -f README.adoc ]]; then
			# echo "\tbuild: warn: missing: README.adoc (pandoc)"
			echo "\twarn: missing: README.adoc\n\t\tadd task: reduce"
			just readme reduce-v
		fi
		just _readme-pandoc
	elif [[ "{{target}}" = "markdown"* ]]; then
		if [[ ! -f README.xml ]]; then
			# echo "\tbuild: warn: missing: README.xml (markdown)"
			echo "\twarn: missing: README.xml\n\t\tadd task: pandoc"
			just readme pandoc-v
		fi
		just _readme-markdown
	fi

	[[ ! "{{target}}" = *"-v" ]] && echo "build: done: {{target}}" || printf ""

#*********************************************

#*********************************************
#### DOCUMENTATION: CHANGELOG
##############################################
# build: 1 - reduced adoc file
@_changelog-reduce:
	echo "\tbuild: reduced"
	asciidoctor-reducer -o CHANGELOG.adoc doc/changelog/index.adoc

# build: 2 - pandoc xml file
@_changelog-pandoc:
	echo "\tbuild: pandoc"
	asciidoctor -b docbook CHANGELOG.adoc

# build: 3 - markdown file
@_changelog-markdown:
	echo "\tbuild: markdown"
	pandoc -f docbook -t markdown_strict CHANGELOG.xml -o CHANGELOG.md

# clean: changelog files
@_changelog-clean:
	echo "\tbuild: clean..."
	rm -vf CHANGELOG.{adoc,xml,md}
	echo "\tbuild: clean: done"

# build CHANGELOG files from doc/changelog/index.adoc (targets build required files if missing): clean, reduce, pandoc, markdown*
changelog target="markdown":
	#!/usr/bin/env zsh
	[[ ! "{{target}}" = *"-v" ]] && echo "building: changelog: {{target}}"

	if [[ "{{target}}" = "clean" ]]; then just _changelog-clean
	elif [[ "{{target}}" = "reduce"* ]]; then
		if [[ -f doc/changelog/index.adoc ]]; then just _changelog-reduce; else echo "\tbuild: warn: missing: doc/changelog/index.adoc (reduce)"; fi
	elif [[ "{{target}}" = "pandoc"* ]]; then
		if [[ ! -f CHANGELOG.adoc ]]; then
			# echo "\tbuild: warn: missing: CHANGELOG.adoc (pandoc)"
			echo "\twarn: missing: CHANGELOG.adoc\n\t\tadd task: reduce"
			just changelog reduce-v
		fi
		just _changelog-pandoc
	elif [[ "{{target}}" = "markdown"* ]]; then
		if [[ ! -f CHANGELOG.xml ]]; then
			# echo "\tbuild: warn: missing: CHANGELOG.xml (markdown)"
			echo "\twarn: missing: CHANGELOG.xml\n\t\tadd task: pandoc"
			just changelog pandoc-v
		fi
		just _changelog-markdown
	fi

	[[ ! "{{target}}" = *"-v" ]] && echo "build: done: {{target}}" || printf ""

#*********************************************
