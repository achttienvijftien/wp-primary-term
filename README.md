# WP Primary Term

Primary term selector for WordPress taxonomies.

## Installation (Development)

Install PHP dependencies:

```bash
composer install
```

Install JavaScript dependencies:

```bash
yarn
```

## Lint

```
yarn lint:js && yarn lint:php
```

Format code:

```
yarn format:js && yarn format:php
```

## Testing

The PHP test suite consists of WordPress integration tests (`WP_UnitTestCase`)
and runs inside the wp-env test environment.

Start the environment (first run pulls the Docker images):

```bash
yarn wp-env start
```

Run the tests:

```bash
yarn test
```

This runs PHPUnit inside the `tests-cli` container against the mounted plugin.
The WordPress test library is provided by wp-env (`WP_TESTS_DIR`); PHPUnit and
the PHPUnit Polyfills are installed as Composer dev dependencies, so make sure
`composer install` has been run first.

## Building

Build for production:

```bash
yarn build
```

Watch mode for development:

```bash
yarn start
```

## Usage

### Enable Primary Term for Taxonomies

Use the `achttienvijftien_primary_term_taxonomies` filter to enable primary term functionality:

```php
add_filter( 'achttienvijftien_primary_term_taxonomies', function( $taxonomies ) {
    $taxonomies[] = 'category';
    $taxonomies[] = 'post_tag';
    return $taxonomies;
} );
```

### Global Functions

All functions take the taxonomy slug first; the post ID is optional and defaults to the current post.

Check whether a post has a primary term (`bool`):

```php
if ( has_primary_term( 'category', $post_id ) ) {
    // ...
}
```

Get the primary term ID (`int`, or `WP_Error` for an invalid taxonomy):

```php
$term_id = get_primary_term_id( 'category', $post_id );
```

Get the primary term object (`WP_Term`, `WP_Error`, or `null` when none resolves):

```php
$term = get_primary_term( 'category', $post_id );
if ( $term instanceof \WP_Term ) {
    echo esc_html( $term->name );
}
```

When no primary term is stored, these fall back to the first assigned term. Disable
that fallback with the `achttienvijftien_primary_term_use_fallback` filter:

```php
add_filter( 'achttienvijftien_primary_term_use_fallback', '__return_false' );
```
