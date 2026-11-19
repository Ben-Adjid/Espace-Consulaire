<?php

namespace Tests\Unit;

use App\Models\Service;
use Tests\TestCase;

/** Pièces personnalisées : lecture des étiquettes de situation en début de ligne. */
class ServiceDocumentsTest extends TestCase
{
    private function service(string $text): Service
    {
        $s = new Service();
        $s->setTranslation('required_documents', 'fr', $text);
        app()->setLocale('fr');

        return $s;
    }

    public function test_untagged_lines_apply_to_everyone(): void
    {
        $items = $this->service("Acte de naissance\n- 2 photos d'identité")->documentItems();

        $this->assertSame([['label' => 'Acte de naissance', 'tags' => []], ['label' => "2 photos d'identité", 'tags' => []]], $items);
    }

    public function test_tags_are_read_and_removed_from_the_label(): void
    {
        $items = $this->service("[renouvellement] Ancien passeport\n[mineur] [premiere] Autorisation parentale signée")->documentItems();

        $this->assertSame('Ancien passeport', $items[0]['label']);
        $this->assertSame(['renewal'], $items[0]['tags']);
        $this->assertSame(['minor', 'first'], $items[1]['tags']);
    }

    public function test_unknown_tags_are_ignored_and_plain_list_keeps_working(): void
    {
        $s = $this->service("[inconnu] Pièce spéciale\n[majeur] Pièce d'identité");

        $this->assertSame([], $s->documentItems()[0]['tags']);
        $this->assertSame(['Pièce spéciale', "Pièce d'identité"], $s->documentsList());
    }
}
