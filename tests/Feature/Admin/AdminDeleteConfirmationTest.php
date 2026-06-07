<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminDeleteConfirmationTest extends TestCase
{
    public function test_admin_delete_confirmation_handler_applies_to_all_delete_forms(): void
    {
        $script = File::get(resource_path('js/app.js'));
        $submitHandlerStart = strpos($script, "document.addEventListener('submit', (event) => {", strpos($script, 'listingDeletePendingForm'));

        $this->assertIsInt($submitHandlerStart);

        $submitHandler = substr($script, $submitHandlerStart, 900);

        $this->assertStringContainsString('input[name="_method"][value="delete"], input[name="_method"][value="DELETE"]', $submitHandler);
        $this->assertStringContainsString('document.body.dataset.deleteConfirmTitle', $submitHandler);
        $this->assertStringContainsString('openListingDeleteModal(form', $submitHandler);
        $this->assertStringNotContainsString("form.closest('.overview-container, .listing-overview-container')", $submitHandler);
    }
}
