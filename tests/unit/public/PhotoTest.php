<?php
declare( strict_types=1 );

require_once __DIR__ . '/class-wh-public-testcase.php';
require_once __DIR__ . '/../../../public/class-wh-photo.php';

final class PhotoTest extends WH_Public_TestCase {

	public function test_builds_cdn_resize_url(): void {
		$src = 'https://cdn.webhotelier.net/photos/properties/demo/zen/1.jpg';
		$url = WH_Photo::resize( $src, 800, 600, 80 );
		$this->assertStringContainsString( 'w=800:h=600:q=80', $url );
		$this->assertStringContainsString( '1.jpg', $url );
	}

	public function test_inserts_dimensions_into_existing_photos_path(): void {
		$src = 'https://cdn.webhotelier.net/photos/abc/def.jpg';
		$url = WH_Photo::resize( $src, 400, 300 );
		$this->assertStringContainsString( 'cdn.webhotelier.net/photos/w=400:h=300:q=85/abc/def.jpg', $url );
	}

	public function test_non_cdn_url_returned_unchanged(): void {
		$src = 'https://example.com/img.jpg';
		$this->assertSame( $src, WH_Photo::resize( $src, 400, 300 ) );
	}

	public function test_empty_returns_empty(): void {
		$this->assertSame( '', WH_Photo::resize( '', 400, 300 ) );
	}
}
