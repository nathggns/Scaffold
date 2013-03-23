<?php defined('SCAFFOLD') or die;

/**
 * Play with strings
 *
 * @author Nathaniel Higgins
 */
class Inflector {

    /**
     * Plural inflector rules
     */
    protected static $plural = [
        'rules' => [
            '/(p)hoto$/i' => '\1hotos',
            '/([b-df-hj-np-tv-z]o)$/i' => '\1es',
            '/(s)tatus$/i' => '\1\2tatuses',
            '/(quiz)$/i' => '\1zes',
            '/^(ox)$/i' => '\1\2en',
            '/([m|l])ouse$/i' => '\1ice',
            '/(matr|vert|ind)(ix|ex)$/i' => '\1ices',
            '/(x|ch|ss|sh)$/i' => '\1es',
            '/([^aeiouy]|qu)y$/i' => '\1ies',
            '/(hive)$/i' => '\1s',
            '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
            '/sis$/i' => 'ses',
            '/([ti])um$/i' => '\1a',
            '/(p)erson$/i' => '\1eople',
            '/(m)an$/i' => '\1en',
            '/(c)hild$/i' => '\1hildren',
            '/(buffal|tomat)o$/i' => '\1\2oes',
            '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us$/i' => '\1i',
            '/us$/i' => 'uses',
            '/(alias)$/i' => '\1es',
            '/(ax|cris|test)is$/i' => '\1es',
            '/s$/i' => 's',
            '/^$/i' => '',
            '/$/i' => 's',
        ],
        'uncountable' => [
            '.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*sheep', 'people'
        ],
        'irregular' => [
            'atlas' => 'atlases',
            'beef' => 'beefs',
            'brother' => 'brothers',
            'cafe' => 'cafes',
            'child' => 'children',
            'corpus' => 'corpuses',
            'cow' => 'cows',
            'ganglion' => 'ganglions',
            'genie' => 'genies',
            'genus' => 'genera',
            'graffito' => 'graffiti',
            'hoof' => 'hoofs',
            'loaf' => 'loaves',
            'man' => 'men',
            'mongoose' => 'mongooses',
            'move' => 'moves',
            'mythos' => 'mythoi',
            'niche' => 'niches',
            'numen' => 'numina',
            'occiput' => 'occiputs',
            'octopus' => 'octopuses',
            'opus' => 'opuses',
            'ox' => 'oxen',
            'penis' => 'penises',
            'person' => 'people',
            'sex' => 'sexes',
            'soliloquy' => 'soliloquies',
            'testis' => 'testes',
            'trilby' => 'trilbys',
            'turf' => 'turfs'
        ],
        'merged' => [],
        'cache' => []
    ];

    /**
     * Uncountable words
     */
    protected static $uncountable = [
        'bison', 'bream', 'breeches', 'buffalo',
        'carp', 'chassis', 'clippers', 'cod', 'coitus', 'corps',
        'debris', 'diabetes', 'equipment', 'flounder',
        'gallows', 'graffiti',
        'headquarters', 'herpes', 'information', 'innings', '.*?media',
        'money', 'moose', 'mumps', 'news', 'nexus',
        'pincers', 'pliers', 'portuguese',
        'proceedings', 'rabies', 'rice', 'rhinoceros', 'salmon', 'scissors',
        'sea[- ]bass', 'series', 'shears', 'species', 'swine',
        'trousers', 'trout', 'tuna'
    ];

    protected static $singular = [
        'rules' => [
            '/(p)hotos$/i' => '\1photo',
            '/(s)tatuses$/i' => '\1\2tatus',
            '/^(.*)(menu)s$/i' => '\1\2',
            '/(quiz)zes$/i' => '\\1',
            '/(matr)ices$/i' => '\1ix',
            '/(vert|ind)ices$/i' => '\1ex',
            '/^(ox)en/i' => '\1',
            '/(alias)(es)*$/i' => '\1',
            '/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i' => '\1us',
            '/([ftw]ax)es/i' => '\1',
            '/(cris|ax|test)es$/i' => '\1is',
            '/(shoe|slave)s$/i' => '\1',
            '/(o)es$/i' => '\1',
            '/ouses$/' => 'ouse',
            '/([^a])uses$/' => '\1us',
            '/([m|l])ice$/i' => '\1ouse',
            '/(x|ch|ss|sh)es$/i' => '\1',
            '/(m)ovies$/i' => '\1\2ovie',
            '/(s)eries$/i' => '\1\2eries',
            '/([^aeiouy]|qu)ies$/i' => '\1y',
            '/([lr])ves$/i' => '\1f',
            '/(tive)s$/i' => '\1',
            '/(hive)s$/i' => '\1',
            '/(drive)s$/i' => '\1',
            '/([^fo])ves$/i' => '\1fe',
            '/(^analy)ses$/i' => '\1sis',
            '/(analy|diagno|^ba|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
            '/([ti])a$/i' => '\1um',
            '/(p)eople$/i' => '\1\2erson',
            '/(m)en$/i' => '\1an',
            '/(c)hildren$/i' => '\1\2hild',
            '/(n)ews$/i' => '\1\2ews',
            '/eaus$/' => 'eau',
            '/^(.*us)$/' => '\\1',
            '/s$/i' => ''
        ],
        'uncountable' => [
            '.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*sheep', '.*ss', 'money'
        ],
        'irregular' => [
            'foes' => 'foe',
            'waves' => 'wave',
            'curves' => 'curve'
        ],
        'merged' => [],
        'cache' => []
    ];


    /**
     * We cache inflected strings to keep this fast
     */
    public static $cache = [
        'plural' => []
    ];

    /**
     * Pluralize a word
     *
     * @param string $word word to pluralize
     * @return string plural of $word
     */
    public static function pluralize($word) {
        // If we have already cached $word, use that
        if ($cached = static::cache('plural', $word)) {
            return $cached;
        }

        // If we haven't merged global uncountable with plural uncountable, do it.
        if (!isset(static::$plural['merged']['uncountable'])) {
            static::$plural['merged']['uncountable'] = array_merge(
                static::$uncountable,
                static::$plural['uncountable']
            );
        }

        // If we haven't compiled the regex into cache, do it.
        if (count(static::$plural['cache']) < 1) {
            static::$plural['cache'] = [
                'uncountable' => '/^((?:' . implode('|', static::$plural['merged']['uncountable']) . '))$/',
                'irregular' => '/(.*)\\b((?:' . implode('|', array_keys(self::$plural['irregular'])) . '))$/i'
            ];
        }

        // Everything is referenced in lower case, so covert to lower case.
        $lower = strtolower($word);

        // If word is uncountable, just return it
        if (preg_match(static::$plural['cache']['uncountable'], $lower, $matches)) {
            return static::cache('plural', $word, $word);
        }

        // If word is irregular, pull it's transformation and return it
        if (preg_match(static::$plural['cache']['irregular'], $lower, $matches)) {
            return static::cache('plural', $word, static::$plural['irregular'][$matches[0]]);
        }

        // Try the individual rules already set
        foreach (static::$plural['rules'] as $rule => $replacement) {
            if (preg_match($rule, $lower)) {
                return self::cache('plural', $word, preg_replace($rule, $replacement, $word));
            }
        }

        // Fallback to just leaving $word alone
        return static::cache('plural', $word, $word);
    }

    /**
     * Singularize a word
     *
     * @param string $word word to singularize
     * @return string singular of $word
     */
    public static function singularize($word) {
        // If we have already cached $word, use that
        if ($cached = static::cache('singular', $word)) {
            return $cached;
        }

        // If we haven't merged global uncountable with singular uncountable, do it.
        if (!isset(static::$singular['merged']['uncountable'])) {
            static::$singular['merged']['uncountable'] = array_merge(
                static::$uncountable,
                static::$singular['uncountable']
            );
        }

        // If we haven't compiled the regex into cache, do it.
        if (count(static::$singular['cache']) < 1) {
            static::$singular['cache'] = [
                'uncountable' => '/^((?:' . implode('|', static::$singular['merged']['uncountable']) . '))$/',
                'irregular' => '/(.*)\\b((?:' . implode('|', array_keys(self::$singular['irregular'])) . '))$/i'
            ];
        }

        // Everything is referenced in lower case, so covert to lower case.
        $lower = strtolower($word);

        // If word is uncountable, just return it
        if (preg_match(static::$singular['cache']['uncountable'], $lower, $matches)) {
            return static::cache('singular', $word, $word);
        }

        // If word is irregular, pull it's transformation and return it
        if (preg_match(static::$singular['cache']['irregular'], $lower, $matches)) {
            return static::cache('singular', $word, static::$singular['irregular'][$matches[0]]);
        }

        // Try the individual rules already set
        foreach (static::$singular['rules'] as $rule => $replacement) {
            if (preg_match($rule, $lower)) {
                return self::cache('singular', $word, preg_replace($rule, $replacement, $word));
            }
        }

        // Fallback to just leaving $word alone
        return static::cache('singular', $word, $word);
    }

    /**
     * Converts 'machine' names into English titles
     *
     * @param string $string string to convert
     * @return string converted string
     */
    public static function titleize($string) {
        // If cached, just return it
        if ($cached = static::cache('titleize', $string)) {
            return $cached;
        }

        return static::cache('titleize', $string, ucwords(static::humanize($string)));
    }

    /**
     * Make a word camelcased
     *
     * @param string $word word to convert
     * @return string coverted $word
     */
    public static function camelize($word) {
        // If cached, just return it
        if ($cached = static::cache('camelize', $word)) {
            return $cached;
        }

        // Convert
        $converted = str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]/', ' ', $word)));

        return static::cache('camelize', $word, $converted);
    }

    /**
     * Convert a string to underscores
     *
     * @param string $string string to convert
     * @return string converted
     */
    public static function underscore($string) {

        if ($cached = static::cache('underscore', $string)) {
            return $cached;
        }

        $converted = strtolower(preg_replace(
            '/[^A-Z^a-z^0-9]+/',
            '_',
            preg_replace(
                '/([a-zd])([A-Z])/',
                '\1_\2',
                preg_replace(
                    '/([A-Z]+)([A-Z][a-z])/',
                    '\1_\2',
                    $string
                )
            )
        ));
        $converted = trim($converted, '_');

        return static::cache('underscore', $string, $converted);
    }

    /**
     * Create a human readable string
     *
     * @param string $string string to convert
     * @return string converted string
     *
     * my_name_is -> My name is
     * YourNameIs -> Your name is
     */
    public static function humanize($string) {
        if ($cached = static::cache('humanize', $string)) {
            return $cached;
        }

        $string = static::underscore($string);
        $converted = ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $string)));

        return static::cache('humanize', $string, $converted);
    }

    /**
     * Convert to a table name
     *
     * @param string $name string to convert
     * @return string converted
     *
     * User -> users
     * Picture -> pictures
     * UserFriend -> user_friends
     */
    public static function tableize($name) {
        if ($cached = static::cache('tableize', $name)) {
            return $cached;
        }

        $converted = static::pluralize(static::underscore($name));

        return static::cache('tableize', $name, $converted);
    }

    /**
     * Convert to a class name
     *
     * @param string $table_name string to convert
     * @return string converted
     *
     * users -> User
     * pictures -> Picture
     * user_friends -> UserFriend
     */
    public static function classify($table_name) {
        if ($cached = static::cache('classify', $table_name)) {
            return $cached;
        }

        $converted = static::camelize(static::singularize($table_name));

        return static::cache('classify', $table_name, $converted);
    }

    /**
     * Ordinalize a number
     *
     * @param int $number number to ordinalize
     * @return string ordinalize $number
     */
    public static function ordinalize($number) {

        if ($cached = static::cache('ordinalize', $number)) {
            return $cached;
        }

        if (in_array($number % 100, range(11, 13))) {
            $converted = $number . 'th';
        }

        switch ($number % 10) {
            case 1:
                $converted = $number . 'st';
            break;

            case 2:
                $converted = $number . 'nd';
            break;

            case 3:
                $converted = $number . 'rd';
            break;

            default:
                $converted = $number . 'th';
            break;
        }

        return static::cache('ordinalize', $number, $converted);
    }

    /**
     * Manage the cache
     *
     * @param string $type type of inflection
     * @param string $word uninflected word
     * @param sring $value inflected word (if setting)
     */
    private static function cache($type, $word, $value = null) {
        if (!isset(static::$cache[$type])) {
            static::$cache[$type] = [];
        }

        if ($value) {
            static::$cache[$type][$word] = $value;
            return $value;
        }

        if (isset(static::$cache[$type][$word])) {
            return static::$cache[$type][$word];
        } else {
            return false;
        }
    }
}
