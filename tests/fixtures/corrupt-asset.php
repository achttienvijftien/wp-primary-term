<?php
/**
 * Intentionally invalid PHP, used as a fixture for
 * AssetTest::test_enqueue_survives_corrupt_asset_file().
 *
 * Do NOT "fix" the syntax error below: the test requires this file to be
 * unparseable so the try/catch around require() in Asset can be verified.
 * Excluded from phpcs via phpcs.xml.dist; not collected by PHPUnit (no *Test.php suffix).
 */
return [ 'version' => ;
