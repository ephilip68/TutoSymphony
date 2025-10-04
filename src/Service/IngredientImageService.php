<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class IngredientImageService
{
    private string $pixabayApiKey;
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client, string $pixabayApiKey)
    {
        $this->client = $client;
        $this->pixabayApiKey = $pixabayApiKey;
    }

    /**
     * 🔍 Récupère une image pertinente depuis Pixabay
     */
    public function getIngredientImage(string $name): ?string
    {
        if (empty($name)) {
            return null;
        }

        // Nettoyage du nom
        $cleanName = strtolower(trim($name));
        $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT', $cleanName);
        $cleanName = preg_replace('/[^a-z0-9 ]/', '', $cleanName);

        // Traductions FR → EN
        $translations = [
            // 🧀 Produits laitiers
            'fromage' => 'cheese dairy',
            'fromage de chevre' => 'goat cheese dairy',
            'lait' => 'milk dairy',
            'beurre' => 'butter dairy',
            'creme fraiche' => 'fresh cream dairy',
            'yaourt' => 'yogurt dairy',

            // 🥦 Légumes
            'salade verte' => 'green salad',
            'tomate' => 'tomato',
            'poivron' => 'bell peppers',
            'carotte' => 'carrots',
            'poireaux' => 'leek',
            'oignon' => 'onion',
            'pomme de terre' => 'potato',
            'courgette' => 'zucchini',
            'aubergine' => 'eggplant',
            'haricot vert' => 'green beans',
            'petit pois' => 'peas',
            'concombre' => 'cucumbers',
            'ail' => 'garlic',
            'champignon' => 'mushroom',
            'chou fleur' => 'cauliflower',
            'brocoli' => 'broccol',
            'epinard' => 'spinach',

            // 🍊 Fruits
            'citron' => 'lemon',
            'orange' => 'orange',
            'pomme' => 'apple',
            'banane' => 'banana',
            'fraise' => 'strawberry',
            'framboise' => 'raspberry',
            'cerise' => 'cherry',
            'abricots secs' => 'dried apricots',
            'poire' => 'pear',
            'mangue' => 'mango',
            'ananas' => 'pineapple',
            'melon' => 'melon',
            'pastèque' => 'watermelon',
            'raisin' => 'grape',
            'kiwi' => 'kiwi',
            'avocat' => 'avocados',

            // 🍗 Viandes & poissons
            'poulet entier' => 'whole chicken raw meat',
            'blanc de poulet' => 'chicken breast raw meat',
            'steak de boeuf' => 'beef steak raw meat',
            'boeuf hache' => 'ground beef meat',
            'agneau' => 'lamb raw meat',
            'porc' => 'pork raw meat',
            'jambon' => 'ham charcuterie meat',
            'bacon' => 'bacon slices meat',
            'filet de saumon' => 'salmon fillet fish',
            'thon' => 'tuna fish',
            'crevette' => 'shrimp seafood',
            'moule' => 'mussel seafood',
            'poisson blanc' => 'white fish fillet seafood',
            'canard' => 'duck meat',

            // 🌰 Fruits secs & graines
            'noix' => 'walnut nuts',
            'noisette' => 'hazelnut nuts',
            'amandes grillees' => 'roasted almonds nuts',
            'pistache' => 'pistachio nuts',
            'cacahuete' => 'peanut nuts',
            'graines de tournesol' => 'sunflower seeds',
            'graines de chia' => 'chia seeds',
            'graines de lin' => 'flax seeds',

            // 🧂 Épices & condiments
            'romarin' => 'rosemary herb',
            'thym' => 'thyme herb',
            'basilic' => 'basil herb',
            'persil' => 'parsley herb',
            'coriandre' => 'coriander herb',
            'cannelle' => 'cinnamon',
            'curcuma' => 'turmeric',
            'poivre' => 'black pepper',
            'sel' => 'salt crystals',
            'huile dolive' => 'olive oil bottle',
            'vinaigre' => 'vinegar bottle',
            'moutarde' => 'mustard sauce',
            'sauce soja' => 'soy sauce bottle',
            'piment' => 'chili pepper spice',

            // 🍞 Autres produits alimentaires
            'miel' => 'honey jar',
            'pain' => 'bread loaf bakery',
            'riz' => 'rice grain',
            'pate' => 'pasta raw',
            'spaghetti' => 'spaghetti pasta',
            'oeuf' => 'egg raw food',
            'farine' => 'flour baking',
            'sucre' => 'sugar baking',
            'chocolat' => 'chocolate bar sweet',
            'cafe' => 'coffee beans',
            'cacao en poudre amer' => 'unsweetened cocoa powder',
            'biscuit cuillere' => 'ladyfinger biscuit dessert',
            'vanille' => 'vanilla pod spice',
            'levure' => 'yeast baking',
            'eau' => 'water glass',
        ];

        // Si l’ingrédient n’est pas dans la liste, enrichir automatiquement la requête
        if (!isset($translations[$cleanName])) {
            $query = $cleanName . ' food ingredient';
        } else {
            $query = $translations[$cleanName];
        }

        try {
            $response = $this->client->request('GET', 'https://pixabay.com/api/', [
                'query' => [
                    'key' => $this->pixabayApiKey,
                    'q' => $query . ' food ingredient',
                    'image_type' => 'photo',
                    'category' => 'food',
                    'per_page' => 100,
                    'safesearch' => true,
                    'lang' => 'en', 'fr',
                ],
            ]);

            $data = $response->toArray();

            if (!empty($data['hits'])) {
                return $data['hits'][0]['webformatURL'];
            }
        } catch (\Throwable $e) {
            // silence
        }

        return null;
    }
}