<?php

declare(strict_types=1);

namespace PHPAdmin\Tests\Unit;

use PHPAdmin\Core\Themes;
use PHPUnit\Framework\TestCase;

class ThemesTest extends TestCase
{
    public function testGetBlueReturnsCorrectPrimaryColor(): void
    {
        $theme = Themes::get('Blue');
        $this->assertIsArray($theme);
        $this->assertArrayHasKey('primary', $theme);
        $this->assertSame('#3B82F6', $theme['primary']);
    }

    public function testGetBlueHasRequiredKeys(): void
    {
        $theme = Themes::get('Blue');
        $this->assertArrayHasKey('name', $theme);
        $this->assertArrayHasKey('primary', $theme);
        $this->assertArrayHasKey('secondary', $theme);
        $this->assertArrayHasKey('light', $theme);
        $this->assertArrayHasKey('dark', $theme);
    }

    public function testAllReturnsFiveThemes(): void
    {
        $themes = Themes::all();
        $this->assertCount(5, $themes);
    }

    public function testFiveStandardThemesPresent(): void
    {
        $themes = Themes::all();
        $this->assertArrayHasKey('Blue', $themes);
        $this->assertArrayHasKey('Purple', $themes);
        $this->assertArrayHasKey('Green', $themes);
        $this->assertArrayHasKey('Orange', $themes);
        $this->assertArrayHasKey('Red', $themes);
    }

    public function testGetUnknownReturnsBlueDefault(): void
    {
        $theme = Themes::get('Unknown');
        $blueTheme = Themes::get('Blue');
        $this->assertSame($blueTheme, $theme);
    }

    public function testGetEmptyStringReturnsBlueDefault(): void
    {
        $theme = Themes::get('');
        $blueTheme = Themes::get('Blue');
        $this->assertSame($blueTheme, $theme);
    }

    public function testAllThemesHaveRequiredKeys(): void
    {
        $themes = Themes::all();
        foreach ($themes as $name => $theme) {
            $this->assertArrayHasKey('name', $theme, "Theme '$name' missing 'name' key");
            $this->assertArrayHasKey('primary', $theme, "Theme '$name' missing 'primary' key");
            $this->assertArrayHasKey('secondary', $theme, "Theme '$name' missing 'secondary' key");
            $this->assertArrayHasKey('light', $theme, "Theme '$name' missing 'light' key");
            $this->assertArrayHasKey('dark', $theme, "Theme '$name' missing 'dark' key");
        }
    }

    public function testThemeHexValues(): void
    {
        $this->assertSame('#3B82F6', Themes::get('Blue')['primary']);
        $this->assertSame('#8B5CF6', Themes::get('Purple')['primary']);
        $this->assertSame('#10B981', Themes::get('Green')['primary']);
        $this->assertSame('#F59E0B', Themes::get('Orange')['primary']);
        $this->assertSame('#EF4444', Themes::get('Red')['primary']);
    }
}
