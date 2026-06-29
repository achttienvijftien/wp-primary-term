# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Fix bug where large amounts of selected terms would not be displayed
- Clear primary term after all terms were deselected or terms were updated without including the current primary term

## [1.0.0] - 2026-06-08

The initial version of this plugin consists of:

- Plugin scaffolding for the `wp-primary-term` WordPress plugin: singleton `Bootstrap`, Composer autoloading,
  `@wordpress/scripts` build tooling, a wp-env test environment, and PHPCS/PHPUnit configuration
- Opt-in primary term support per taxonomy via the `achttienvijftien_primary_term_taxonomies` filter
- Primary term stored as post meta (`_primary_{taxonomy}`), registered with `register_meta` and exposed in the REST API
  with an `edit_post` authorization callback
- Gutenberg editor integration that extends the taxonomy panel (`editor.PostTaxonomyType`) with a primary term
  `SelectControl`, shown only once more than one term is assigned; admin assets load solely on post edit screens for
  post types tied to an enabled taxonomy
- Global helper functions `has_primary_term()`, `get_primary_term_id()`, and `get_primary_term()`, each taking the
  taxonomy slug first with an optional post ID
- Fallback to the first assigned term when no primary term is stored, toggleable with the
  `achttienvijftien_primary_term_use_fallback` filter
- `achttienvijftien_primary_term` filter on the resolved primary term object

[unreleased]: https://github.com/achttienvijftien/wp-primary-term/compare/1.0.0...main

[1.0.0]: https://github.com/achttienvijftien/wp-primary-term/releases/tag/1.0.0
