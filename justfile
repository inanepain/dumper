# inanepain/dumper
# version: $Id$
# date: $Date$

set shell := ["zsh", "-cu"]
set positional-arguments

project := "inane\\dumper"

# list recipes
_default:
    @echo "{{project}}:"
    @just --list --list-heading ''

# compile reduced README.adoc for project from README code
@readme:
	asciidoctor-reducer -o README.adoc doc/README.adoc

# generate php doc
@doc:
	mkdir -p doc/code
	phpdoc -d src -t doc/code --title="{{project}}" --defaultpackagename="Inane"
