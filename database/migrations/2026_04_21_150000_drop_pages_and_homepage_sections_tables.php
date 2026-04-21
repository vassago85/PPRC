<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the WordPress-style CMS layer.
 *
 * `pages` and `homepage_sections` were a generic page-builder and homepage
 * block model bolted onto the admin. The public site is hand-crafted Blade
 * (homepage, /about, /membership) backed by real domain data (ExcoMember,
 * Event, Announcement), so the CMS tables are dead weight.
 *
 * This migration is idempotent on fresh installs because the create
 * migrations for these tables were removed from the repo in the same change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('homepage_sections');
    }

    public function down(): void
    {
        // Deliberately empty. These tables are gone for good; rebuilding the
        // CMS would require restoring the models, resources, and views, not
        // just recreating schema.
    }
};
