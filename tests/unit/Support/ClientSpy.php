<?php

namespace WH\Tests\Unit\Support;

/**
 * Helper for resource-API tests: builds a Mockery WH_Client that asserts the
 * exact method/path/args/opts a resource passes through, and returns a sentinel.
 */
trait ClientSpy {

	/**
	 * Build a mock WH_Client expecting a single get() call.
	 *
	 * @param string $path     Expected path.
	 * @param array  $args     Expected args (use \Mockery::on for loose matching if needed).
	 * @param mixed  $return   Value to return.
	 * @param array  $opts     Expected opts (default: any).
	 * @return \Mockery\MockInterface
	 */
	protected function client_expecting_get( $path, $args, $return, $opts = null ) {
		$client = \Mockery::mock( \WH_Client::class );
		$matcher = $client->shouldReceive( 'get' )->once();
		if ( null === $opts ) {
			// opts is optional: assert path/args verbatim, accept the call whether or
			// not a trailing opts argument was supplied.
			$matcher->withArgs(
				static function ( $actual_path, $actual_args = array() ) use ( $path, $args ) {
					return $actual_path === $path && $actual_args === $args;
				}
			);
		} else {
			$matcher->with( $path, $args, $opts );
		}
		$matcher->andReturn( $return );
		return $client;
	}

	/**
	 * Build a mock WH_Client expecting a single post() call.
	 *
	 * @param string $path   Expected path.
	 * @param array  $args   Expected args.
	 * @param mixed  $return Value to return.
	 * @return \Mockery\MockInterface
	 */
	protected function client_expecting_post( $path, $args, $return ) {
		$client = \Mockery::mock( \WH_Client::class );
		$client->shouldReceive( 'post' )->once()->with( $path, $args )->andReturn( $return );
		return $client;
	}
}
