<?php

namespace Tests\Feature;

use App\Models\PdfTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfTemplateSanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'estimator_admin']);
    }

    public function test_loop_tags_wrapped_in_p_are_unwrapped()
    {
        $rawHtml = '
        <table>
            <tbody>
                <p>{LOOP_ITEMS}</p>
                <tr><td>{item_name}</td></tr>
                <p>{END_LOOP}</p>
            </tbody>
            <tfoot><tr><td>Total:</td><td>{grand_total}</td></tr></tfoot>
        </table>';

        $this->actingAs($this->user)
            ->post(route('pdf-templates.store'), [
                'name' => 'Unwrap Test',
                'html_content' => $rawHtml,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#000000',
                'font_family' => 'Helvetica',
            ]);

        $template = PdfTemplate::where('name', 'Unwrap Test')->first();
        $this->assertNotNull($template);

        $savedHtml = $template->html_content;

        // Assert absence of wrapping p tags around logic
        $this->assertStringNotContainsString('<p>{LOOP_ITEMS}</p>', $savedHtml);
        $this->assertStringNotContainsString('<p>{END_LOOP}</p>', $savedHtml);

        // Assert logic tags are present
        $this->assertStringContainsString('{LOOP_ITEMS}', $savedHtml);
        $this->assertStringContainsString('{END_LOOP}', $savedHtml);

        // Structure check: Logic inside tbody
        $tbodyPos = strpos($savedHtml, '<tbody');
        $loopPos = strpos($savedHtml, '{LOOP_ITEMS}');
        $this->assertTrue($loopPos > $tbodyPos, 'Loop should stay inside tbody');
    }

    public function test_loop_tags_with_attributes_in_p_are_unwrapped()
    {
        // WYSIWYG might add styles or classes to the p tag
        $rawHtml = '
        <table>
            <tbody>
                <p style="text-align: center;">{LOOP_ITEMS}</p>
                <tr><td>{item_name}</td></tr>
                <p class="foo">{END_LOOP}</p>
            </tbody>
            <tfoot><tr><td>Total:</td><td>{grand_total}</td></tr></tfoot>
        </table>';

        $this->actingAs($this->user)
            ->post(route('pdf-templates.store'), [
                'name' => 'Unwrap Attributes Test',
                'html_content' => $rawHtml,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#000000',
                'font_family' => 'Helvetica',
            ]);

        $template = PdfTemplate::where('name', 'Unwrap Attributes Test')->first();
        $savedHtml = $template->html_content;

        $this->assertStringNotContainsString('<p', $savedHtml, 'P tags should be removed from logic lines');
        $this->assertStringContainsString('{LOOP_ITEMS}', $savedHtml);
    }

    public function test_if_tags_are_unwrapped()
    {
        $rawHtml = '<p>{IF_room_based}</p><div>Content</div><p>{ENDIF}</p>{LOOP_ITEMS}{END_LOOP}{grand_total}';

        $this->actingAs($this->user)
            ->post(route('pdf-templates.store'), [
                'name' => 'Unwrap IF Test',
                'html_content' => $rawHtml,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#000000',
                'font_family' => 'Helvetica',
            ]);

        $template = PdfTemplate::where('name', 'Unwrap IF Test')->first();
        $savedHtml = $template->html_content;

        $this->assertStringNotContainsString('<p>{IF_room_based}</p>', $savedHtml);
        $this->assertStringContainsString('{IF_room_based}', $savedHtml);
    }
    public function test_loop_tags_wrapped_in_div_are_unwrapped()
    {
        $rawHtml = '
        <table>
            <tbody>
                <div>{LOOP_ITEMS}</div>
                <tr><td>{item_name}</td></tr>
                <div class="test">{END_LOOP}</div>
            </tbody>
            <tfoot><tr><td>Total:</td><td>{grand_total}</td></tr></tfoot>
        </table>';

        $this->actingAs($this->user)
            ->post(route('pdf-templates.store'), [
                'name' => 'Unwrap DIV Test',
                'html_content' => $rawHtml,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#000000',
                'font_family' => 'Helvetica',
            ]);

        $template = PdfTemplate::where('name', 'Unwrap DIV Test')->first();
        $savedHtml = $template->html_content;

        $this->assertStringNotContainsString('<div>{LOOP_ITEMS}</div>', $savedHtml);
        $this->assertStringContainsString('{LOOP_ITEMS}', $savedHtml);
    }
    public function test_table_cells_wrapped_in_p_are_unwrapped()
    {
        // This simulates the "parent table not found" crash scenario
        // where <td> is wrapped in <p>, causing Purifier to close the table.
        $rawHtml = '
        <table>
            <tbody>
                <tr>
                    <p><td>Cell 1</td></p>
                    <div><td>Cell 2</td></div>
                </tr>
            </tbody>
            <tfoot><tr><td>Total:</td><td>{grand_total}</td></tr></tfoot>
        </table>
        {LOOP_ITEMS}{END_LOOP}';

        $this->actingAs($this->user)
            ->post(route('pdf-templates.store'), [
                'name' => 'Unwrap Table Cells Test',
                'html_content' => $rawHtml,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#000000',
                'font_family' => 'Helvetica',
            ]);

        $template = PdfTemplate::where('name', 'Unwrap Table Cells Test')->first();
        $savedHtml = $template->html_content;

        // The structure should be valid <tr><td>... without the p/div wrappers
        $this->assertStringNotContainsString('<p><td>', $savedHtml);
        $this->assertStringNotContainsString('<div><td>', $savedHtml);

        // Assert table integration
        // p inside tr is invalid, so if it wasn't unwrapped, Purifier would have ejected it or closed table.
        // We want to see <tr><td> directly (ignoring whitespace)
        // Adjust regex to be flexible with whitespace
        $this->assertMatchesRegularExpression('/<tr>\s*<td>Cell 1<\/td>/', $savedHtml);
        $this->assertMatchesRegularExpression('/<\/td>\s*<td>Cell 2<\/td>/', $savedHtml);
    }
    public function test_nested_wrappers_are_unwrapped_cleanly()
    {
        // Recursively wrapped: <div><p><tr>...</tr></p></div>
        // Should remove ALL wrappers and leave no trailing </div> or </p>
        $rawHtml = '
        <table>
            <tbody>
                <div>
                    <p>
                        <tr><td>Nested Cell</td></tr>
                    </p>
                </div>
            </tbody>
            <tfoot><tr><td>Total:</td><td>{grand_total}</td></tr></tfoot>
        </table>
        {LOOP_ITEMS}{END_LOOP}';

        $this->actingAs($this->user)
            ->post(route('pdf-templates.store'), [
                'name' => 'Unwrap Nested Test',
                'html_content' => $rawHtml,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#000000',
                'font_family' => 'Helvetica',
            ]);

        $template = PdfTemplate::where('name', 'Unwrap Nested Test')->first();
        $savedHtml = $template->html_content;

        // Verify clean unwrap
        $this->assertStringNotContainsString('<p>', $savedHtml);
        $this->assertStringNotContainsString('</p>', $savedHtml);
        $this->assertStringNotContainsString('<div>', $savedHtml);
        $this->assertStringNotContainsString('</div>', $savedHtml); // This asserts no trailing </div>

        // precise check
        $this->assertMatchesRegularExpression('/<tbody>\s*<tr>\s*<td>Nested Cell<\/td>\s*<\/tr>\s*<\/tbody>/s', $savedHtml);
    }
    public function test_wrappers_with_nbsp_are_unwrapped_cleanly()
    {
        // Wrapper with &nbsp; at the end: <div><tr>...</tr>&nbsp;</div>
        // Common in editors. If we remove opening div but fail to remove closing div, we get orphaned </div>.
        $rawHtml = '
        <table>
            <tbody>
                <div>
                    <tr><td>Cell</td></tr>&nbsp;
                </div>
                <p>
                    <tr><td>Cell 2</td></tr>&nbsp;
                </p>
            </tbody>
            <tfoot><tr><td>Total:</td><td>{grand_total}</td></tr></tfoot>
        </table>
        {LOOP_ITEMS}{END_LOOP}';

        $this->actingAs($this->user)
            ->post(route('pdf-templates.store'), [
                'name' => 'Unwrap NBSP Test',
                'html_content' => $rawHtml,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'primary_color' => '#000000',
                'font_family' => 'Helvetica',
            ]);

        $template = PdfTemplate::where('name', 'Unwrap NBSP Test')->first();
        $savedHtml = $template->html_content;

        $this->assertStringNotContainsString('<div>', $savedHtml, 'Opening div should be gone');
        $this->assertStringNotContainsString('</div>', $savedHtml, 'Closing div should be gone despite &nbsp;');
        $this->assertStringNotContainsString('<p>', $savedHtml);
        $this->assertStringNotContainsString('</p>', $savedHtml);

        $this->assertStringContainsString('<tr><td>Cell</td></tr>', $savedHtml);
    }
}
