<?php

namespace App\Controller;

use App\Entity\Element;
use App\Entity\Joueur;
use App\Entity\ModeTournoi;
use App\Entity\Theme;
use App\Entity\Tournoi;
use App\Entity\TypeMedia;
use App\Form\TournoiType;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tournois')]
class TournoiController extends AbstractController
{
    private const MAX_ELEMENTS = 64;

    private const MIN_ELEMENTS_POUR_PUBLIER = 8;

    /*
     * ==============================
     * Utilitaires metier (tournois)
     * ==============================
     */

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function initialiserFormulaireCreation(FormInterface $form): void
    {
        $form->get('mode')->setData(null);
        $form->get('nomThemeA')->setData('');
        $form->get('nomThemeB')->setData('');
        $form->get('elementsPayload')->setData('[]');
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function preRemplirFormEdition(Tournoi $tournoi, FormInterface $form): void
    {
        $form->get('mode')->setData($tournoi->getMode()->value);
        $form->get('nomThemeA')->setData($tournoi->getThemeA()?->getNom() ?? '');
        $form->get('nomThemeB')->setData($tournoi->getThemeB()?->getNom() ?? '');
        $form->get('elementsPayload')->setData($this->construirePayloadEdition($tournoi));
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function construirePayloadEdition(Tournoi $tournoi): string
    {
        $themeAId = $tournoi->getThemeA()?->getId();
        $themeBId = $tournoi->getThemeB()?->getId();
        $elements = [];

        foreach ($tournoi->getElementsActifs() as $element) {
            $themeSlot = null;
            $themeId = $element->getTheme()?->getId();

            if ($themeAId !== null && $themeId === $themeAId) {
                $themeSlot = 'A';
            } elseif ($themeBId !== null && $themeId === $themeBId) {
                $themeSlot = 'B';
            }

            $elements[] = [
                'id' => sprintf('element-%d', (int) $element->getId()),
                'title' => $element->getTitre(),
                'url' => $element->getMediaUrl(),
                'mediaType' => $element->getMediaType()->value,
                'theme' => $themeSlot,
                'startTime' => null,
                'endTime' => null,
            ];
        }

        $json = json_encode($elements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '[]';
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function estSoumissionBrouillon(Request $request): bool
    {
        return (string) $request->request->get('submit_intent', '') === 'draft'
            || $request->request->has('save_draft');
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function decoderJsonObjet(Request $request): array
    {
        $contenu = trim((string) $request->getContent());
        if ($contenu === '') {
            throw new InvalidArgumentException('Aucune donnée reçue.');
        }

        try {
            $donnees = json_decode($contenu, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Les données envoyées ne sont pas valides.');
        }

        if (!is_array($donnees)) {
            throw new InvalidArgumentException('Les données envoyées ne sont pas valides.');
        }

        return $donnees;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function extraireIdNumerique(mixed $valeur, string $prefixe = ''): ?int
    {
        if (is_int($valeur) && $valeur > 0) {
            return $valeur;
        }

        if (!is_string($valeur)) {
            return null;
        }

        $texte = trim($valeur);
        if ($texte === '') {
            return null;
        }

        if ($prefixe !== '' && str_starts_with($texte, $prefixe)) {
            $texte = substr($texte, strlen($prefixe));
        }

        if (!ctype_digit($texte)) {
            return null;
        }

        $id = (int) $texte;

        return $id > 0 ? $id : null;
    }

    /**
     * @return array{id: string, title: string, url: string, mediaType: string, theme: string, startTime: ?int, endTime: ?int, videoSource: ?string}
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function construireDonneesElementWizard(
        Element $element,
        Tournoi $tournoi,
        ?int $startTime = null,
        ?int $endTime = null,
        ?string $videoSource = null
    ): array {
        $themeAId = $tournoi->getThemeA()?->getId();
        $themeBId = $tournoi->getThemeB()?->getId();
        $themeElementId = $element->getTheme()?->getId();

        $themeSlot = '';
        if ($themeAId !== null && $themeAId === $themeElementId) {
            $themeSlot = 'A';
        } elseif ($themeBId !== null && $themeBId === $themeElementId) {
            $themeSlot = 'B';
        }

        return [
            'id' => sprintf('element-%d', (int) $element->getId()),
            'title' => $element->getTitre(),
            'url' => $element->getMediaUrl(),
            'mediaType' => $element->getMediaType()->value,
            'theme' => $themeSlot,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'videoSource' => $videoSource,
        ];
    }

    /**
     * Valide et applique le formulaire complet (cover + choix + publication).
     *
     * Cette fonction est le coeur du workflow: elle lit le JSON des elements,
     * valide le mode, cree/recupere les themes si necessaire, calcule la taille
     * du tableau automatiquement, puis remplace les elements du tournoi.
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function validerEtAppliquerElements(
        FormInterface $form,
        Tournoi $tournoi,
        ThemeRepository $themeRepository,
        EntityManagerInterface $entityManager,
        bool $modeModifiable = true,
        bool $sauvegardeBrouillon = false
    ): bool {
        try {
            // 1. Verifie le mode soumis (ou conserve le mode existant en edition)
            $modeSoumis = $modeModifiable
                ? (string) $form->get('mode')->getData()
                : $tournoi->getMode()->value;

            if (!in_array($modeSoumis, [ModeTournoi::LIBRE->value, ModeTournoi::THEME_VS_THEME->value], true)) {
                $form->addError(new FormError('Le format du tournoi n\'est pas valide.'));

                return false;
            }

            $mode = ModeTournoi::from($modeSoumis);
            $tournoi->setMode($mode);

            // 1.b Valide l image de couverture (uniquement fichier televerse)
            $urlCouverture = (string) ($tournoi->getCoverImageUrl() ?? '');
            if (!$this->estCheminImageTeleverseeValide($urlCouverture)) {
                $form->addError(new FormError('L\'image de couverture n\'est pas valide.'));

                return false;
            }

            // 2. Decode puis normalise la liste des elements ajoutes dans l onglet Choices
            $elements = $this->parserElementsPayload((string) $form->get('elementsPayload')->getData());
            $totalElements = count($elements);

            if ($totalElements > self::MAX_ELEMENTS) {
                $form->addError(new FormError(sprintf('Tu peux ajouter au maximum %d éléments.', self::MAX_ELEMENTS)));

                return false;
            }

            if ($mode === ModeTournoi::THEME_VS_THEME) {
                // 3. En mode theme, les noms des deux themes sont obligatoires et differents
                $nomThemeA = trim((string) $form->get('nomThemeA')->getData());
                $nomThemeB = trim((string) $form->get('nomThemeB')->getData());

                if ($nomThemeA === '' || $nomThemeB === '') {
                    $form->addError(new FormError('En mode thème vs thème, les deux thèmes sont obligatoires.'));

                    return false;
                }

                if (mb_strtolower($nomThemeA) === mb_strtolower($nomThemeB)) {
                    $form->addError(new FormError('Les deux thèmes doivent être différents.'));

                    return false;
                }

                // 4. Compte les elements affectes a chaque theme et calcule la taille automatique
                [$compteThemeA, $compteThemeB] = $this->compterElementsParTheme($elements);
                $tailleAuto = $this->plusGrandePuissanceDeDeuxInferieureOuEgale(min($compteThemeA, $compteThemeB));

                if (!$sauvegardeBrouillon && $tailleAuto < self::MIN_ELEMENTS_POUR_PUBLIER) {
                    $form->addError(new FormError(sprintf(
                        'Pour publier, il faut au moins %d éléments dans chaque thème (A : %d, B : %d).',
                        self::MIN_ELEMENTS_POUR_PUBLIER,
                        $compteThemeA,
                        $compteThemeB
                    )));

                    return false;
                }

                // 5. Prepare/associe les themes puis applique la nouvelle liste d elements
                $themeA = $this->trouverOuCreerTheme($nomThemeA, $themeRepository, $entityManager);
                $themeB = $this->trouverOuCreerTheme($nomThemeB, $themeRepository, $entityManager);

                $tournoi
                    ->setThemeA($themeA)
                    ->setThemeB($themeB)
                    ->setTailleTableauCible($tailleAuto >= self::MIN_ELEMENTS_POUR_PUBLIER ? $tailleAuto : null);

                $this->remplacerElements($tournoi, $elements, $themeA, $themeB, $entityManager);

                return true;
            }

            // 6. En mode libre: minimum 8 elements, taille auto basee sur le total
            if (!$sauvegardeBrouillon && $totalElements < self::MIN_ELEMENTS_POUR_PUBLIER) {
                $form->addError(new FormError(sprintf(
                    'Pour publier, il faut au moins %d éléments.',
                    self::MIN_ELEMENTS_POUR_PUBLIER
                )));

                return false;
            }

            $tailleAuto = $this->plusGrandePuissanceDeDeuxInferieureOuEgale($totalElements);
            if (!$sauvegardeBrouillon && $tailleAuto < self::MIN_ELEMENTS_POUR_PUBLIER) {
                $form->addError(new FormError('Nombre d\'éléments insuffisant pour calculer une taille de tournoi valide.'));

                return false;
            }

            // 7. Le mode libre n utilise pas de themes, puis remplace la liste des elements
            $tournoi
                ->setThemeA(null)
                ->setThemeB(null)
                ->setTailleTableauCible($tailleAuto >= self::MIN_ELEMENTS_POUR_PUBLIER ? $tailleAuto : null);

            $this->remplacerElements($tournoi, $elements, null, null, $entityManager);

            return true;
        } catch (InvalidArgumentException $exception) {
            $form->addError(new FormError($exception->getMessage()));

            return false;
        }
    }

    /**
     * Convertit la valeur JSON du champ cache en tableau d elements valides.
     *
        * @return array<int, array{idElement: ?int, titre: string, url: string, type: TypeMedia, themeSlot: null|'A'|'B'}>
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function parserElementsPayload(string $payload): array
    {
        $payload = trim($payload);
        if ($payload === '') {
            return [];
        }

        try {
            $elementsBruts = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Le format des éléments est invalide. Recharge la page puis recommence.');
        }

        if (!is_array($elementsBruts)) {
            throw new InvalidArgumentException('Les éléments transmis sont invalides.');
        }

        $elements = [];

        foreach ($elementsBruts as $index => $elementBrut) {
            if (!is_array($elementBrut)) {
                throw new InvalidArgumentException(sprintf('Élément %d invalide.', $index + 1));
            }

            $titre = trim((string) ($elementBrut['title'] ?? $elementBrut['titre'] ?? ''));
            $url = trim((string) ($elementBrut['url'] ?? ''));
            $typeTexte = trim((string) ($elementBrut['mediaType'] ?? $elementBrut['type'] ?? ''));

            if ($titre === '' || $url === '') {
                throw new InvalidArgumentException(sprintf('Élément %d invalide : nom et média sont obligatoires.', $index + 1));
            }

            if (mb_strlen($titre) > 255) {
                throw new InvalidArgumentException(sprintf('Élément %d invalide : le nom est trop long.', $index + 1));
            }

            $type = $this->determinerTypeMedia($url, $typeTexte);

            $startTime = $this->parserTempsVideo($elementBrut['startTime'] ?? null, 'startTime', $index + 1);
            $endTime = $this->parserTempsVideo($elementBrut['endTime'] ?? null, 'endTime', $index + 1);

            if ($startTime !== null && $endTime !== null && $endTime <= $startTime) {
                throw new InvalidArgumentException(sprintf('Élément %d invalide : endTime doit être supérieur à startTime.', $index + 1));
            }

            if ($type === TypeMedia::VIDEO) {
                $url = $this->normaliserUrlYoutube($url, $startTime, $endTime, $index + 1);
            } elseif (!$this->estUrlMediaValide($url)) {
                throw new InvalidArgumentException(sprintf('Élément %d invalide : URL image incorrecte.', $index + 1));
            }

            $themeTexte = strtoupper(trim((string) ($elementBrut['theme'] ?? '')));
            $themeSlot = in_array($themeTexte, ['A', 'B'], true) ? $themeTexte : null;
            $idElement = $this->extraireIdNumerique($elementBrut['id'] ?? null, 'element-');

            $elements[] = [
                'idElement' => $idElement,
                'titre' => $titre,
                'url' => $url,
                'type' => $type,
                'themeSlot' => $themeSlot,
            ];
        }

        return $elements;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function parserTempsVideo(mixed $valeur, string $champ, int $position): ?int
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (!is_numeric($valeur)) {
            throw new InvalidArgumentException(sprintf('Élément %d invalide : %s doit être numérique.', $position, $champ));
        }

        $temps = (int) $valeur;
        if ($temps < 0) {
            throw new InvalidArgumentException(sprintf('Élément %d invalide : %s ne peut pas être négatif.', $position, $champ));
        }

        return $temps;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function estUrlMediaValide(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }

        return str_starts_with($url, '/') && str_contains($url, '/uploads/');
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function estCheminImageTeleverseeValide(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        return str_starts_with($url, '/') && str_contains($url, '/uploads/tournois/');
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function determinerTypeMedia(string $url, string $typeTexte = ''): TypeMedia
    {
        $typeTexte = mb_strtolower(trim($typeTexte));
        if (in_array($typeTexte, ['video', 'vid', 'youtube'], true)) {
            return TypeMedia::VIDEO;
        }

        if (in_array($typeTexte, ['image', 'img', 'photo'], true)) {
            return TypeMedia::IMAGE;
        }

        $urlBasse = mb_strtolower($url);

        if (str_contains($urlBasse, 'youtube.com')
            || str_contains($urlBasse, 'youtu.be')
            || str_contains($urlBasse, '.mp4')
            || str_contains($urlBasse, '.webm')
            || str_contains($urlBasse, '.mov')) {
            return TypeMedia::VIDEO;
        }

        return TypeMedia::IMAGE;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function normaliserUrlYoutube(string $url, ?int $startTime, ?int $endTime, int $position): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('Élément %d invalide : URL vidéo incorrecte.', $position));
        }

        $youtubeId = $this->extraireYoutubeId($url);
        if ($youtubeId === null) {
            throw new InvalidArgumentException(sprintf('Élément %d invalide : seule une URL YouTube est acceptée pour une vidéo.', $position));
        }

        $parametres = [];
        if ($startTime !== null && $startTime > 0) {
            $parametres['start'] = $startTime;
        }

        if ($endTime !== null && $endTime > 0) {
            $parametres['end'] = $endTime;
        }

        $query = $parametres === [] ? '' : '?'.http_build_query($parametres);

        return 'https://www.youtube.com/embed/'.$youtubeId.$query;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function extraireYoutubeId(string $url): ?string
    {
        $urlDecoupee = parse_url($url);
        if ($urlDecoupee === false || !isset($urlDecoupee['host'])) {
            return null;
        }

        $host = mb_strtolower($urlDecoupee['host']);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $path = $urlDecoupee['path'] ?? '';
        parse_str($urlDecoupee['query'] ?? '', $query);

        if ($host === 'youtu.be') {
            return $this->youtubeIdValide(trim($path, '/')) ? trim($path, '/') : null;
        }

        if ($host === 'youtube.com' || $host === 'm.youtube.com') {
            if (isset($query['v']) && is_string($query['v']) && $this->youtubeIdValide($query['v'])) {
                return $query['v'];
            }

            if (str_starts_with($path, '/embed/')) {
                $id = trim(substr($path, strlen('/embed/')), '/');

                return $this->youtubeIdValide($id) ? $id : null;
            }

            if (str_starts_with($path, '/shorts/')) {
                $id = trim(substr($path, strlen('/shorts/')), '/');

                return $this->youtubeIdValide($id) ? $id : null;
            }
        }

        return null;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function youtubeIdValide(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1;
    }

    /**
     * @param array<int, array{themeSlot: null|'A'|'B'}> $elements
     * @return array{0: int, 1: int}
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function compterElementsParTheme(array $elements): array
    {
        $compteThemeA = 0;
        $compteThemeB = 0;

        foreach ($elements as $element) {
            if (($element['themeSlot'] ?? null) === 'A') {
                ++$compteThemeA;
            }

            if (($element['themeSlot'] ?? null) === 'B') {
                ++$compteThemeB;
            }
        }

        return [$compteThemeA, $compteThemeB];
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function plusGrandePuissanceDeDeuxInferieureOuEgale(int $valeur): int
    {
        if ($valeur < 1) {
            return 0;
        }

        $plafond = min($valeur, self::MAX_ELEMENTS);
        $resultat = 1;

        while (($resultat * 2) <= $plafond) {
            $resultat *= 2;
        }

        return $resultat;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function trouverOuCreerTheme(
        string $nomTheme,
        ThemeRepository $themeRepository,
        EntityManagerInterface $entityManager
    ): Theme {
        $slug = $this->slugifier($nomTheme);
        $theme = $themeRepository->findOneBy(['slug' => $slug]);
        if ($theme !== null) {
            return $theme;
        }

        $theme = new Theme();
        $theme->setNom($nomTheme);
        $theme->setSlug($slug);

        $entityManager->persist($theme);

        return $theme;
    }

    /**
        * @param array<int, array{idElement: ?int, titre: string, url: string, type: TypeMedia, themeSlot: null|'A'|'B'}> $elements
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function remplacerElements(
        Tournoi $tournoi,
        array $elements,
        ?Theme $themeA,
        ?Theme $themeB,
        EntityManagerInterface $entityManager
    ): void {
        $elementsExistants = array_values(array_filter(
            $tournoi->getElements()->toArray(),
            static fn ($item): bool => $item instanceof Element
        ));

        $elementsExistantsParId = [];
        foreach ($elementsExistants as $elementExistant) {
            $idElement = $elementExistant->getId();
            if (is_int($idElement) && $idElement > 0) {
                $elementsExistantsParId[$idElement] = $elementExistant;
            }
        }

        $idsConserves = [];

        foreach ($elements as $elementData) {
            $theme = null;
            if (($elementData['themeSlot'] ?? null) === 'A') {
                $theme = $themeA;
            } elseif (($elementData['themeSlot'] ?? null) === 'B') {
                $theme = $themeB;
            }

            $idElementSoumis = $elementData['idElement'] ?? null;
            $element = null;

            if (is_int($idElementSoumis) && isset($elementsExistantsParId[$idElementSoumis])) {
                $element = $elementsExistantsParId[$idElementSoumis];
                $idsConserves[$idElementSoumis] = true;
            }

            if (!($element instanceof Element)) {
                $element = new Element();
                $tournoi->addElement($element);
            }

            $element
                ->setTitre($elementData['titre'])
                ->setMedia($elementData['url'], $elementData['type'])
                ->setTheme($theme)
                ->setActif(true);
        }

        foreach ($elementsExistants as $elementExistant) {
            $idElementExistant = $elementExistant->getId();
            if (is_int($idElementExistant) && isset($idsConserves[$idElementExistant])) {
                continue;
            }

            if ($this->elementEstReferenceDansHistorique($elementExistant, $entityManager)) {
                $elementExistant->setActif(false);

                continue;
            }

            $tournoi->removeElement($elementExistant);
            $entityManager->remove($elementExistant);
        }
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function elementEstReferenceDansHistorique(Element $element, EntityManagerInterface $entityManager): bool
    {
        $idElement = $element->getId();
        if (!is_int($idElement) || $idElement < 1) {
            return false;
        }

        $sql = <<<'SQL'
SELECT CASE
    WHEN EXISTS(SELECT 1 FROM classement_perdant cp WHERE cp.id_element = :id)
        OR EXISTS(SELECT 1 FROM duel d WHERE d.id_element_a = :id OR d.id_element_b = :id)
        OR EXISTS(SELECT 1 FROM repechage r WHERE r.id_perdant = :id OR r.id_vainqueur_cible = :id)
        OR EXISTS(SELECT 1 FROM decision_impair di WHERE di.id_element_impair = :id)
    THEN 1
    ELSE 0
END AS est_reference
SQL;

        $resultat = $entityManager->getConnection()->fetchOne($sql, ['id' => $idElement]);

        return (string) $resultat === '1';
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function slugifier(string $texte): string
    {
        $texte = trim($texte);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texte);
        $base = $ascii === false ? $texte : $ascii;

        $base = mb_strtolower($base);
        $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? '';
        $base = trim($base, '-');

        return $base === '' ? 'theme-'.bin2hex(random_bytes(4)) : $base;
    }

    /*
     * =====================
     * Routes HTTP (actions)
     * =====================
     */

    /**
     * Reçoit une image envoyée depuis le wizard puis retourne son URL publique.
     */
    #[Route('/upload-image', name: 'app_tournoi_upload_image', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function televerserImage(Request $request): JsonResponse
    {
        $fichier = $request->files->get('image');
        if (!$fichier instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Aucun fichier image envoyé.'], Response::HTTP_BAD_REQUEST);
        }

        if (($fichier->getSize() ?? 0) > (12 * 1024 * 1024)) {
            return new JsonResponse(['error' => 'Image trop lourde (12 Mo max).'], Response::HTTP_BAD_REQUEST);
        }

        $mimesAutorises = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        $mime = (string) $fichier->getMimeType();
        if (!array_key_exists($mime, $mimesAutorises)) {
            return new JsonResponse(['error' => 'Format d\'image non pris en charge.'], Response::HTTP_BAD_REQUEST);
        }

        $repertoireDestination = (string) $this->getParameter('kernel.project_dir').'/public/uploads/tournois';
        if (!is_dir($repertoireDestination) && !mkdir($repertoireDestination, 0775, true) && !is_dir($repertoireDestination)) {
            return new JsonResponse(['error' => 'Impossible de préparer le dossier d\'upload.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $nomFichier = 'img-'.bin2hex(random_bytes(12)).'.'.$mimesAutorises[$mime];
            $fichier->move($repertoireDestination, $nomFichier);
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'Impossible d\'envoyer l\'image.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $basePath = rtrim((string) $request->getBasePath(), '/');
        $urlPrefix = $basePath === '' ? '' : $basePath;

        return new JsonResponse([
            'url' => $urlPrefix.'/uploads/tournois/'.$nomFichier,
        ]);
    }

    /**
     * Crée ou met à jour un élément du wizard en autosauvegarde.
     */
    #[Route('/wizard/upsert-element', name: 'app_tournoi_wizard_upsert_element', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enregistrerElementWizard(
        Request $request,
        EntityManagerInterface $entityManager,
        ThemeRepository $themeRepository
    ): JsonResponse {
        $joueur = $this->getUser();
        if (!$joueur instanceof Joueur) {
            return new JsonResponse(['error' => 'Session utilisateur invalide.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $payload = $this->decoderJsonObjet($request);
            $tournoiPayload = $payload['tournoi'] ?? null;
            $elementPayload = $payload['element'] ?? null;

            if (!is_array($tournoiPayload) || !is_array($elementPayload)) {
                throw new InvalidArgumentException('Les données envoyées sont incomplètes.');
            }

            $nom = trim((string) ($tournoiPayload['nom'] ?? ''));
            $description = trim((string) ($tournoiPayload['description'] ?? ''));
            $coverImageUrl = trim((string) ($tournoiPayload['coverImageUrl'] ?? ''));
            $modeSoumis = trim((string) ($tournoiPayload['mode'] ?? ''));
            $nomThemeA = trim((string) ($tournoiPayload['nomThemeA'] ?? ''));
            $nomThemeB = trim((string) ($tournoiPayload['nomThemeB'] ?? ''));

            if (mb_strlen($nom) < 3 || mb_strlen($nom) > 120) {
                throw new InvalidArgumentException('Le titre du tournoi est invalide.');
            }

            if ($description === '' || mb_strlen($description) > 5000) {
                throw new InvalidArgumentException('La description du tournoi est invalide.');
            }

            if (!$this->estCheminImageTeleverseeValide($coverImageUrl)) {
                throw new InvalidArgumentException('L\'image de couverture n\'est pas valide.');
            }

            if (!in_array($modeSoumis, [ModeTournoi::LIBRE->value, ModeTournoi::THEME_VS_THEME->value], true)) {
                throw new InvalidArgumentException('Le format du tournoi n\'est pas valide.');
            }

            $mode = ModeTournoi::from($modeSoumis);

            $tournoiId = $this->extraireIdNumerique($payload['tournoiId'] ?? null);
            $tournoi = null;
            if ($tournoiId !== null) {
                $tournoi = $entityManager->find(Tournoi::class, $tournoiId);

                if (!$tournoi instanceof Tournoi) {
                    throw new InvalidArgumentException('Tournoi introuvable.');
                }

                if ($tournoi->getCreateur()?->getId() !== $joueur->getId()) {
                    return new JsonResponse(['error' => 'Ce tournoi ne t\'appartient pas.'], Response::HTTP_FORBIDDEN);
                }
            }

            if (!$tournoi instanceof Tournoi) {
                $tournoi = new Tournoi();
                $tournoi->setCreateur($joueur);
                $tournoi->setBrouillon(true);
                $entityManager->persist($tournoi);
            }

            $tournoi
                ->setNom($nom)
                ->setDescription($description)
                ->setCoverImageUrl($coverImageUrl)
                ->setMode($mode)
                ->setTailleTableauCible(null);

            $themeA = null;
            $themeB = null;

            if ($mode === ModeTournoi::THEME_VS_THEME) {
                if ($nomThemeA === '' || $nomThemeB === '') {
                    throw new InvalidArgumentException('Les deux thèmes sont obligatoires pour ce mode.');
                }

                if (mb_strtolower($nomThemeA) === mb_strtolower($nomThemeB)) {
                    throw new InvalidArgumentException('Les deux thèmes doivent être différents.');
                }

                $themeA = $this->trouverOuCreerTheme($nomThemeA, $themeRepository, $entityManager);
                $themeB = $this->trouverOuCreerTheme($nomThemeB, $themeRepository, $entityManager);

                $tournoi
                    ->setThemeA($themeA)
                    ->setThemeB($themeB);
            } else {
                $tournoi
                    ->setThemeA(null)
                    ->setThemeB(null);
            }

            $elementsNormalises = $this->parserElementsPayload((string) json_encode([$elementPayload], JSON_THROW_ON_ERROR));
            if (!isset($elementsNormalises[0])) {
                throw new InvalidArgumentException('Élément invalide.');
            }

            $elementData = $elementsNormalises[0];
            $elementId = $this->extraireIdNumerique($elementPayload['id'] ?? null, 'element-');

            $element = null;
            if ($elementId !== null) {
                $elementExistant = $entityManager->find(Element::class, $elementId);
                if (!$elementExistant instanceof Element || $elementExistant->getTournoi()?->getId() !== $tournoi->getId()) {
                    throw new InvalidArgumentException('Élément introuvable.');
                }

                $element = $elementExistant;
            }

            if (!$element instanceof Element) {
                if ($tournoi->getNombreElementsActifs() >= self::MAX_ELEMENTS) {
                    throw new InvalidArgumentException(sprintf('Maximum de %d éléments atteint.', self::MAX_ELEMENTS));
                }

                $element = new Element();
                $tournoi->addElement($element);
            }

            $themeElement = null;
            if (($elementData['themeSlot'] ?? null) === 'A') {
                $themeElement = $themeA;
            } elseif (($elementData['themeSlot'] ?? null) === 'B') {
                $themeElement = $themeB;
            }

            $element
                ->setTitre($elementData['titre'])
                ->setMedia($elementData['url'], $elementData['type'])
                ->setTheme($themeElement)
                ->setActif(true);

            $startTime = $this->parserTempsVideo($elementPayload['startTime'] ?? null, 'startTime', 1);
            $endTime = $this->parserTempsVideo($elementPayload['endTime'] ?? null, 'endTime', 1);
            if ($startTime !== null && $endTime !== null && $endTime <= $startTime) {
                throw new InvalidArgumentException('Le temps de fin doit être supérieur au temps de début.');
            }

            $videoSource = isset($elementPayload['videoSource']) ? trim((string) $elementPayload['videoSource']) : null;
            if ($videoSource === '') {
                $videoSource = null;
            }

            $entityManager->flush();

            return new JsonResponse([
                'tournoiId' => $tournoi->getId(),
                'editUrl' => $this->generateUrl('app_tournoi_edit', ['id' => $tournoi->getId()]),
                'element' => $this->construireDonneesElementWizard($element, $tournoi, $startTime, $endTime, $videoSource),
            ]);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (JsonException) {
            return new JsonResponse(['error' => 'Les données envoyées ne sont pas valides.'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Supprime un élément du wizard côté serveur.
     */
    #[Route('/wizard/delete-element', name: 'app_tournoi_wizard_delete_element', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function supprimerElementWizard(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $joueur = $this->getUser();
        if (!$joueur instanceof Joueur) {
            return new JsonResponse(['error' => 'Session utilisateur invalide.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $payload = $this->decoderJsonObjet($request);

            $tournoiId = $this->extraireIdNumerique($payload['tournoiId'] ?? null);
            $elementId = $this->extraireIdNumerique($payload['elementId'] ?? null, 'element-');

            if ($tournoiId === null || $elementId === null) {
                throw new InvalidArgumentException('Les données envoyées sont incomplètes.');
            }

            $tournoi = $entityManager->find(Tournoi::class, $tournoiId);
            if (!$tournoi instanceof Tournoi) {
                throw new InvalidArgumentException('Tournoi introuvable.');
            }

            if ($tournoi->getCreateur()?->getId() !== $joueur->getId()) {
                return new JsonResponse(['error' => 'Ce tournoi ne t\'appartient pas.'], Response::HTTP_FORBIDDEN);
            }

            $element = $entityManager->find(Element::class, $elementId);
            if (!$element instanceof Element || $element->getTournoi()?->getId() !== $tournoi->getId()) {
                throw new InvalidArgumentException('Élément introuvable.');
            }

            $tournoi->setTailleTableauCible(null);

            if ($this->elementEstReferenceDansHistorique($element, $entityManager)) {
                $element->setActif(false);
            } else {
                $tournoi->removeElement($element);
                $entityManager->remove($element);
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
            ]);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Affiche puis traite la création d'un tournoi.
     */
    #[Route('/creer', name: 'app_tournoi_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function creer(Request $request, EntityManagerInterface $entityManager, ThemeRepository $themeRepository): Response
    {
        $joueur = $this->getUser();
        if (!$joueur instanceof Joueur) {
            throw $this->createAccessDeniedException('Session utilisateur invalide.');
        }

        $tournoi = new Tournoi();
        $tournoi->setCreateur($joueur);

        $form = $this->createForm(TournoiType::class, $tournoi);
        $this->initialiserFormulaireCreation($form);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $estSauvegardeBrouillon = $this->estSoumissionBrouillon($request);

            if ($this->validerEtAppliquerElements($form, $tournoi, $themeRepository, $entityManager, true, $estSauvegardeBrouillon)) {
                $tournoi->setBrouillon($estSauvegardeBrouillon);
                $entityManager->persist($tournoi);
                $entityManager->flush();

                if ($estSauvegardeBrouillon) {
                    $this->addFlash('success', 'Brouillon enregistré. Tu peux le reprendre depuis ton profil.');
                } else {
                    $this->addFlash('success', 'Tournoi créé avec succès.');
                }

                return $this->redirectToRoute('app_compte');
            }
        }

        return $this->render('tournoi/form.html.twig', [
            'tournoiForm' => $form,
            'edition' => false,
            'estBrouillon' => false,
            'peutEnregistrerBrouillon' => true,
        ]);
    }

    /**
     * Affiche puis traite l'édition d'un tournoi existant.
     */
    #[Route('/{id}/modifier', name: 'app_tournoi_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function modifier(
        Tournoi $tournoi,
        Request $request,
        EntityManagerInterface $entityManager,
        ThemeRepository $themeRepository
    ): Response {
        $joueur = $this->getUser();
        if (!$joueur instanceof Joueur || $tournoi->getCreateur()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException('Ce tournoi ne t\'appartient pas.');
        }

        $form = $this->createForm(TournoiType::class, $tournoi, [
            'mode_disabled' => true,
            'mode_initial' => $tournoi->getMode()->value,
        ]);
        $this->preRemplirFormEdition($tournoi, $form);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etaitBrouillon = $tournoi->isBrouillon();
            $estSauvegardeBrouillon = $this->estSoumissionBrouillon($request);

            if ($this->validerEtAppliquerElements($form, $tournoi, $themeRepository, $entityManager, false, $estSauvegardeBrouillon)) {
                $tournoi->setBrouillon($estSauvegardeBrouillon);
                $entityManager->flush();

                if ($estSauvegardeBrouillon) {
                    $this->addFlash('success', 'Brouillon mis à jour.');
                } elseif ($etaitBrouillon) {
                    $this->addFlash('success', 'Tournoi publié avec succès.');
                } else {
                    $this->addFlash('success', 'Tournoi mis à jour.');
                }

                return $this->redirectToRoute('app_compte');
            }
        }

        return $this->render('tournoi/form.html.twig', [
            'tournoiForm' => $form,
            'edition' => true,
            'tournoi' => $tournoi,
            'estBrouillon' => $tournoi->isBrouillon(),
            'peutEnregistrerBrouillon' => $tournoi->isBrouillon(),
        ]);
    }

    /**
     * Supprime un tournoi publié appartenant à l'utilisateur connecté.
     */
    #[Route('/{id}/supprimer', name: 'app_tournoi_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function supprimer(Tournoi $tournoi, Request $request, EntityManagerInterface $entityManager): Response
    {
        $joueur = $this->getUser();
        if (!$joueur instanceof Joueur || $tournoi->getCreateur()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException('Ce tournoi ne t\'appartient pas.');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_tournoi_'.$tournoi->getId(), $token)) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Réessaie.');

            return $this->redirectToRoute('app_compte');
        }

        $entityManager->remove($tournoi);
        $entityManager->flush();

        $this->addFlash('success', 'Tournoi supprimé.');

        return $this->redirectToRoute('app_compte');
    }
}
