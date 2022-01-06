<?php

/**
 * @defgroup i18n I18N
 * Implements localization concerns such as locale files, time zones, and country lists.
 */

/**
 * @file classes/i18n/PKPLocale.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPLocale
 * @ingroup i18n
 *
 * @brief Provides methods for loading locale data and translating strings identified by unique keys
 */

namespace PKP\i18n;

use APP\i18n\AppLocale;
use Illuminate\Support\Facades\DB;
use PKP\cache\CacheManager;
use PKP\config\Config;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\db\XMLDAO;
use PKP\facades\Repo;
use PKP\plugins\HookRegistry;
use PKP\plugins\PluginRegistry;

if (!defined('LOCALE_DEFAULT')) {
    define('LOCALE_DEFAULT', Config::getVar('i18n', 'locale'));
}
if (!defined('LOCALE_ENCODING')) {
    define('LOCALE_ENCODING', Config::getVar('i18n', 'client_charset'));
}

// Error types for locale checking.
// Note: Cannot use numeric symbols for the constants below because
// array_merge_recursive doesn't treat numeric keys nicely.
define('LOCALE_ERROR_MISSING_KEY', 'LOCALE_ERROR_MISSING_KEY');
define('LOCALE_ERROR_EXTRA_KEY', 'LOCALE_ERROR_EXTRA_KEY');
define('LOCALE_ERROR_DIFFERING_PARAMS', 'LOCALE_ERROR_DIFFERING_PARAMS');
define('LOCALE_ERROR_MISSING_FILE', 'LOCALE_ERROR_MISSING_FILE');

define('EMAIL_ERROR_MISSING_EMAIL', 'EMAIL_ERROR_MISSING_EMAIL');
define('EMAIL_ERROR_EXTRA_EMAIL', 'EMAIL_ERROR_EXTRA_EMAIL');
define('EMAIL_ERROR_DIFFERING_PARAMS', 'EMAIL_ERROR_DIFFERING_PARAMS');

// Shared locale components
define('LOCALE_COMPONENT_PKP_COMMON', 0x00000001);
define('LOCALE_COMPONENT_PKP_ADMIN', 0x00000002);
define('LOCALE_COMPONENT_PKP_INSTALLER', 0x00000003);
define('LOCALE_COMPONENT_PKP_MANAGER', 0x00000004);
define('LOCALE_COMPONENT_PKP_READER', 0x00000005);
define('LOCALE_COMPONENT_PKP_SUBMISSION', 0x00000006);
define('LOCALE_COMPONENT_PKP_USER', 0x00000007);
define('LOCALE_COMPONENT_PKP_GRID', 0x00000008);
define('LOCALE_COMPONENT_PKP_DEFAULT', 0x00000009);
define('LOCALE_COMPONENT_PKP_EDITOR', 0x0000000A);
define('LOCALE_COMPONENT_PKP_REVIEWER', 0x0000000B);
define('LOCALE_COMPONENT_PKP_API', 0x0000000C);

// Application-specific locale components
define('LOCALE_COMPONENT_APP_COMMON', 0x00000100);
define('LOCALE_COMPONENT_APP_MANAGER', 0x00000101);
define('LOCALE_COMPONENT_APP_SUBMISSION', 0x00000102);
define('LOCALE_COMPONENT_APP_AUTHOR', 0x00000103);
define('LOCALE_COMPONENT_APP_EDITOR', 0x00000104);
define('LOCALE_COMPONENT_APP_ADMIN', 0x00000105);
define('LOCALE_COMPONENT_APP_DEFAULT', 0x00000106);
define('LOCALE_COMPONENT_APP_API', 0x00000107);
define('LOCALE_COMPONENT_APP_EMAIL', 0x00000108);

use PKP\session\SessionManager;

// (Let PHPUnit tests define this first if necessary)
class PKPLocale
{
    public const MASTER_LOCALE = 'en_US';
    public const LOCALE_REGISTRY_FILE = 'registry/locales.xml';

    public static $request;

    /**
     * Get all supported UI locales for the current context.
     *
     * @return array
     */
    public static function getSupportedLocales()
    {
        static $supportedLocales;
        if (!isset($supportedLocales)) {
            if (defined('SESSION_DISABLE_INIT')) {
                $supportedLocales = AppLocale::getAllLocales();
            } elseif (($context = self::$request->getContext())) {
                $supportedLocales = $context->getSupportedLocaleNames();
            } else {
                $site = self::$request->getSite();
                $supportedLocales = $site->getSupportedLocaleNames();
            }
        }
        return $supportedLocales;
    }

    /**
     * Get all supported form locales for the current context.
     *
     * @return array
     */
    public static function getSupportedFormLocales()
    {
        static $supportedFormLocales;
        if (!isset($supportedFormLocales)) {
            if (defined('SESSION_DISABLE_INIT')) {
                $supportedFormLocales = AppLocale::getAllLocales();
            } elseif (($context = self::$request->getContext())) {
                $supportedFormLocales = $context->getSupportedFormLocaleNames();
            } else {
                $site = self::$request->getSite();
                $supportedFormLocales = $site->getSupportedLocaleNames();
            }
        }
        return $supportedFormLocales;
    }

    /**
     * Return the key name of the user's currently selected locale (default
     * is "en_US" for U.S. English).
     *
     * @return string
     */
    public static function getLocale()
    {
        static $currentLocale;
        if (!isset($currentLocale)) {
            if (defined('SESSION_DISABLE_INIT')) {
                // If the locale is specified in the URL, allow
                // it to override. (Necessary when locale is
                // being set, as cookie will not yet be re-set)
                $locale = AppLocale::$request->getUserVar('setLocale');
                if (empty($locale) || !in_array($locale, array_keys(AppLocale::getSupportedLocales()))) {
                    $locale = self::$request->getCookieVar('currentLocale');
                }
            } else {
                $sessionManager = SessionManager::getManager();
                $session = $sessionManager->getUserSession();
                $locale = self::$request->getUserVar('uiLocale');

                $context = self::$request->getContext();
                $site = self::$request->getSite();

                if (!isset($locale)) {
                    $locale = $session->getSessionVar('currentLocale');
                }

                if (!isset($locale)) {
                    $locale = self::$request->getCookieVar('currentLocale');
                }

                if (isset($locale)) {
                    // Check if user-specified locale is supported
                    if ($context != null) {
                        $locales = $context->getSupportedLocaleNames();
                    } else {
                        $locales = $site->getSupportedLocaleNames();
                    }

                    if (!in_array($locale, array_keys($locales))) {
                        unset($locale);
                    }
                }

                if (!isset($locale)) {
                    // Use context/site default
                    if ($context != null) {
                        $locale = $context->getPrimaryLocale();
                    }

                    if (!isset($locale)) {
                        $locale = $site->getPrimaryLocale();
                    }
                }
            }

            if (!AppLocale::isLocaleValid($locale)) {
                $locale = LOCALE_DEFAULT;
            }

            $currentLocale = $locale;
        }
        return $currentLocale;
    }

    /**
     * Get the stack of "important" locales, most important first.
     *
     * @return array
     */
    public static function getLocalePrecedence()
    {
        static $localePrecedence;
        if (!isset($localePrecedence)) {
            $localePrecedence = [AppLocale::getLocale()];

            $context = self::$request->getContext();
            if ($context && !in_array($context->getPrimaryLocale(), $localePrecedence)) {
                $localePrecedence[] = $context->getPrimaryLocale();
            }

            $site = self::$request->getSite();
            if ($site && !in_array($site->getPrimaryLocale(), $localePrecedence)) {
                $localePrecedence[] = $site->getPrimaryLocale();
            }
        }
        return $localePrecedence;
    }

    /**
     * Retrieve the primary locale of the current context.
     *
     * @return string
     */
    public static function getPrimaryLocale()
    {
        static $locale;
        if ($locale) {
            return $locale;
        }

        if (defined('SESSION_DISABLE_INIT')) {
            return $locale = LOCALE_DEFAULT;
        }

        $context = self::$request->getContext();

        if (isset($context)) {
            $locale = $context->getPrimaryLocale();
        }

        if (!isset($locale)) {
            $site = self::$request->getSite();
            $locale = $site->getPrimaryLocale();
        }

        if (!isset($locale) || !AppLocale::isLocaleValid($locale)) {
            $locale = LOCALE_DEFAULT;
        }

        return $locale;
    }

    /**
     * Get a list of locale files currently registered, either in all
     * locales (in an array for each locale), or for a specific locale.
     *
     * @param string $locale Locale identifier (optional)
     */
    public static function &getLocaleFiles($locale = null)
    {
        $localeFiles = & Registry::get('localeFiles', true, []);
        if ($locale !== null) {
            if (!isset($localeFiles[$locale])) {
                $localeFiles[$locale] = [];
            }
            return $localeFiles[$locale];
        }
        return $localeFiles;
    }

    /**
     * Add octothorpes to a key name for presentation of the key as missing.
     *
     * @param string $key
     *
     * @return string
     */
    public static function addOctothorpes($key)
    {
        return '##' . htmlentities($key) . '##';
    }

    /**
     * Translate a string using the selected locale.
     * Substitution works by replacing tokens like "{$foo}" with the value
     * of the parameter named "foo" (if supplied).
     *
     * @param string $key
     * @param array $params named substitution parameters
     * @param string $locale the locale to use
     * @param callable $missingKeyHandler Callback to be invoked when a key cannot be found.
     *
     * @return string
     */
    public static function translate($key, $params = [], $locale = null, $missingKeyHandler = [__CLASS__, 'addOctothorpes'])
    {
        if (!isset($locale)) {
            $locale = AppLocale::getLocale();
        }
        if (($key = trim($key)) == '') {
            return '';
        }

        $localeFiles = & AppLocale::getLocaleFiles($locale);
        $value = '';
        for ($i = count($localeFiles) - 1 ; $i >= 0 ; $i --) {
            $value = $localeFiles[$i]->translate($key, $params);
            if ($value !== null) {
                return $value;
            }
        }

        // Add a missing key to the debug notes.
        $notes = & Registry::get('system.debug.notes');
        $notes[] = ['debug.notes.missingLocaleKey', ['key' => $key]];

        if (!HookRegistry::call('PKPLocale::translate', [&$key, &$params, &$locale, &$localeFiles, &$value])) {
            // Add some octothorpes to missing keys to make them more obvious
            return $missingKeyHandler($key);
        } else {
            return $value;
        }
    }

    /**
     * Initialize the locale system.
     *
     * @param PKPRequest $request
     */
    public static function initialize($request)
    {
        self::$request = $request;

        // Use defaults if locale info unspecified.
        $locale = AppLocale::getLocale();
        setlocale(LC_ALL, $locale . '.' . LOCALE_ENCODING, $locale);
        putenv("LC_ALL=${locale}");

        AppLocale::registerLocaleFile($locale, "lib/pkp/locale/${locale}/common.po");

        // Set site time zone
        // Starting from PHP 5.3.0 PHP will throw an E_WARNING if the default
        // time zone is not set and date/time functions are used
        // http://pl.php.net/manual/en/function.date-default-timezone-set.php
        $timeZone = self::getTimeZone();
        date_default_timezone_set($timeZone);

        if (Config::getVar('general', 'installed')) {
            // Set the time zone for DB
            // Get the offset from UTC
            $now = new \DateTime();
            $mins = $now->getOffset() / 60;
            $sgn = ($mins < 0 ? -1 : 1);
            $mins = abs($mins);
            $hrs = floor($mins / 60);
            $mins -= $hrs * 60;
            $offset = sprintf('%+d:%02d', $hrs * $sgn, $mins);

            switch (Config::getVar('database', 'driver')) {
                case 'mysql':
                case 'mysqli':
                    DB::statement('SET time_zone = \'' . $offset . '\'');
                    break;
                case 'postgres':
                case 'postgres64':
                case 'postgres7':
                case 'postgres8':
                case 'postgres9':
                    DB::statement('SET TIME ZONE INTERVAL \'' . $offset . '\' HOUR TO MINUTE');
                    break;
                default: assert(false);
            }
        }
    }

    /**
     * Build an associative array of LOCALE_COMPOMENT_... => filename
     * (use getFilenameComponentMap instead)
     *
     * @param string $locale
     *
     * @return array
     */
    public static function makeComponentMap($locale)
    {
        $baseDir = "lib/pkp/locale/${locale}/";

        return [
            LOCALE_COMPONENT_PKP_COMMON => $baseDir . 'common.po',
            LOCALE_COMPONENT_PKP_ADMIN => $baseDir . 'admin.po',
            LOCALE_COMPONENT_PKP_INSTALLER => $baseDir . 'installer.po',
            LOCALE_COMPONENT_PKP_MANAGER => $baseDir . 'manager.po',
            LOCALE_COMPONENT_PKP_READER => $baseDir . 'reader.po',
            LOCALE_COMPONENT_PKP_SUBMISSION => $baseDir . 'submission.po',
            LOCALE_COMPONENT_PKP_EDITOR => $baseDir . 'editor.po',
            LOCALE_COMPONENT_PKP_REVIEWER => $baseDir . 'reviewer.po',
            LOCALE_COMPONENT_PKP_USER => $baseDir . 'user.po',
            LOCALE_COMPONENT_PKP_GRID => $baseDir . 'grid.po',
            LOCALE_COMPONENT_PKP_DEFAULT => $baseDir . 'default.po',
            LOCALE_COMPONENT_PKP_API => $baseDir . 'api.po',
        ];
    }

    /**
     * Get an associative array of LOCALE_COMPOMENT_... => filename
     *
     * @param string $locale
     *
     * @return array
     */
    public static function getFilenameComponentMap($locale)
    {
        $filenameComponentMap = & Registry::get('localeFilenameComponentMap', true, []);
        if (!isset($filenameComponentMap[$locale])) {
            $filenameComponentMap[$locale] = AppLocale::makeComponentMap($locale);
        }
        return $filenameComponentMap[$locale];
    }

    /**
     * Load a set of locale components. Parameters of mixed length may
     * be supplied, each a LOCALE_COMPONENT_... constant. An optional final
     * parameter may be supplied to specify the locale (e.g. 'en_US').
     */
    public static function requireComponents()
    {
        $params = func_get_args();

        $paramCount = count($params);
        if ($paramCount === 0) {
            return;
        }

        // Get the locale
        $lastParam = $params[$paramCount - 1];
        if (is_string($lastParam)) {
            $locale = $lastParam;
            $paramCount--;
        } else {
            $locale = AppLocale::getLocale();
        }

        // Backwards compatibility: the list used to be supplied
        // as an array in the first parameter.
        if (is_array($params[0])) {
            $params = $params[0];
            $paramCount = count($params);
        }

        // Go through and make sure each component is loaded if valid.
        $loadedComponents = & Registry::get('loadedLocaleComponents', true, []);
        $filenameComponentMap = AppLocale::getFilenameComponentMap($locale);
        for ($i = 0; $i < $paramCount; $i++) {
            $component = $params[$i];

            // Don't load components twice
            if (isset($loadedComponents[$locale][$component])) {
                continue;
            }

            // Validate component
            if (!isset($filenameComponentMap[$component])) {
                fatalError('Unknown locale component ' . $component);
            }

            $filename = $filenameComponentMap[$component];
            AppLocale::registerLocaleFile($locale, $filename);
            $loadedComponents[$locale][$component] = true;
        }
    }

    /**
     * Register a locale file against the current list.
     *
     * @param string $locale Locale key
     * @param string $filename Filename to new locale XML file
     * @param bool $addToTop Whether to add to the top of the list (true)
     * 	or the bottom (false). Allows overriding.
     */
    public static function registerLocaleFile($locale, $filename, $addToTop = false)
    {
        $localeFiles = & AppLocale::getLocaleFiles($locale);
        $localeFile = new LocaleFile($locale, $filename);

        if (!HookRegistry::call('PKPLocale::registerLocaleFile::isValidLocaleFile', [&$localeFile])) {
            if (!$localeFile->isValid()) {
                return null;
            }
        }
        if ($addToTop) {
            // Work-around: unshift by reference.
            array_unshift($localeFiles, '');
            $localeFiles[0] = & $localeFile;
        } else {
            $localeFiles[] = & $localeFile;
        }
        HookRegistry::call('PKPLocale::registerLocaleFile', [&$locale, &$filename, &$addToTop]);
        return $localeFile;
    }

    /**
     * Get the stylesheet filename for a particular locale.
     *
     * @param string $locale
     *
     * @return string or null if none configured.
     */
    public static function getLocaleStyleSheet($locale)
    {
        $contents = & AppLocale::_getAllLocalesCacheContent();
        if (isset($contents[$locale]['stylesheet'])) {
            return $contents[$locale]['stylesheet'];
        }
        return null;
    }

    /**
     * Get the reading direction for a particular locale.
     *
     * A locale can specify a reading direction with the `direction` attribute. If no
     * direction is specified, defaults to `ltr` (left-to-right). The only
     * other value that is expected is `rtl`. This value is used in HTML and
     * CSS markup to present a right-to-left layout.
     *
     * @param string $locale
     *
     * @return string
     */
    public static function getLocaleDirection($locale)
    {
        $contents = & AppLocale::_getAllLocalesCacheContent();
        if (isset($contents[$locale]['direction'])) {
            return $contents[$locale]['direction'];
        }
        return 'ltr';
    }

    /**
     * Determine whether or not a locale is marked incomplete.
     *
     * @param string $locale xx_XX symbolic name of locale to check
     *
     * @return bool
     */
    public static function isLocaleComplete($locale)
    {
        $contents = & AppLocale::_getAllLocalesCacheContent();
        if (!isset($contents[$locale])) {
            return false;
        }
        if (isset($contents[$locale]['complete']) && $contents[$locale]['complete'] == 'false') {
            return false;
        }
        return true;
    }

    /**
     * Determine whether or not a locale uses family name first.
     *
     * @param string $locale xx_XX symbolic name of locale to check
     *
     * @return bool
     */
    public static function isLocaleWithFamilyFirst($locale)
    {
        $contents = & AppLocale::_getAllLocalesCacheContent();
        if (isset($contents[$locale]) && isset($contents[$locale]['familyFirst']) && $contents[$locale]['familyFirst'] == 'true') {
            return true;
        }
        return false;
    }

    /**
     * Check if the supplied locale is currently installable.
     *
     * @param string $locale
     *
     * @return bool
     */
    public static function isLocaleValid($locale)
    {
        if (empty($locale)) {
            return false;
        }
        // variants can be composed of five to eight letters, or of four characters starting with a digit
        if (!preg_match('/^[a-z][a-z]_[A-Z][A-Z](@([A-Za-z0-9]{5,8}|\d[A-Za-z0-9]{3}))?$/', $locale)) {
            return false;
        }
        if (file_exists('locale/' . $locale)) {
            return true;
        }
        return false;
    }

    /**
     * Load a locale list from a file.
     *
     * @param string $filename
     *
     * @return array
     */
    public static function &loadLocaleList($filename)
    {
        $xmlDao = new XMLDAO();
        $data = $xmlDao->parseStruct($filename, ['locale']);
        $allLocales = [];

        // Build array with ($localKey => $localeName)
        if (isset($data['locale'])) {
            foreach ($data['locale'] as $localeData) {
                $allLocales[$localeData['attributes']['key']] = $localeData['attributes'];
            }
        }

        return $allLocales;
    }

    /**
     * Return a list of all available locales.
     *
     * @return array
     */
    public static function &getAllLocales()
    {
        $rawContents = & AppLocale::_getAllLocalesCacheContent();
        $allLocales = [];

        foreach ($rawContents as $locale => $contents) {
            $allLocales[$locale] = $contents['name'];
        }

        // if client encoding is set to iso-8859-1, transcode locales from utf8
        if (LOCALE_ENCODING == 'iso-8859-1') {
            $allLocales = array_map('utf8_decode', $allLocales);
        }

        return $allLocales;
    }

    /**
     * Install support for a new locale.
     *
     * @param string $locale
     */
    public static function installLocale($locale)
    {
        // Install default locale-specific data
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_EMAIL, $locale);
        Repo::emailTemplate()->dao->installEmailTemplateLocaleData(Repo::emailTemplate()->dao->getMainEmailTemplatesFilename(), [$locale]);

        // Load all plugins so they can add locale data if needed
        $categories = PluginRegistry::getCategories();
        foreach ($categories as $category) {
            PluginRegistry::loadCategory($category);
        }
        HookRegistry::call('PKPLocale::installLocale', [&$locale]);
    }

    /**
     * Uninstall support for an existing locale.
     *
     * @param string $locale
     */
    public static function uninstallLocale($locale)
    {
        // Delete locale-specific data
        Repo::emailTemplate()->dao->deleteEmailTemplatesByLocale($locale);
        Repo::emailTemplate()->dao->deleteDefaultEmailTemplatesByLocale($locale);
    }

    /**
     * Reload locale-specific data.
     *
     * @param string $locale
     */
    public static function reloadLocale($locale)
    {
        AppLocale::installLocale($locale);
    }

    /**
     * Given a locale string, get the list of parameter references of the
     * form {$myParameterName}.
     *
     * @param string $source
     *
     * @return array
     */
    public static function getParameterNames($source)
    {
        $matches = null;
        PKPString::regexp_match_all('/({\$[^}]+})/' /* '/{\$[^}]+})/' */, $source, $matches);
        array_shift($matches); // Knock the top element off the array
        if (isset($matches[0])) {
            return $matches[0];
        }
        return [];
    }

    /**
     * Translate the ISO 2-letter language string (ISO639-1)
     * into a ISO compatible 3-letter string (ISO639-2b).
     *
     * @param string $iso2Letter
     *
     * @return string the translated string or null if we
     *  don't know about the given language.
     */
    public static function get3LetterFrom2LetterIsoLanguage($iso2Letter)
    {
        assert(strlen($iso2Letter) == 2);
        $locales = & AppLocale::_getAllLocalesCacheContent();
        foreach ($locales as $locale => $localeData) {
            if (substr($locale, 0, 2) == $iso2Letter) {
                assert(isset($localeData['iso639-2b']));
                return $localeData['iso639-2b'];
            }
        }
        return null;
    }

    /**
     * Translate the ISO 3-letter language string (ISO639-2b)
     * into a ISO compatible 2-letter string (ISO639-1).
     *
     * @param string $iso3Letter
     *
     * @return string the translated string or null if we
     *  don't know about the given language.
     */
    public static function get2LetterFrom3LetterIsoLanguage($iso3Letter)
    {
        assert(strlen($iso3Letter) == 3);
        $locales = & AppLocale::_getAllLocalesCacheContent();
        foreach ($locales as $locale => $localeData) {
            assert(isset($localeData['iso639-2b']));
            if ($localeData['iso639-2b'] == $iso3Letter) {
                return substr($locale, 0, 2);
            }
        }
        return null;
    }

    /**
     * Translate the PKP locale identifier into an
     * ISO639-2b compatible 3-letter string.
     *
     * @param string $locale
     *
     * @return string
     */
    public static function get3LetterIsoFromLocale($locale)
    {
        assert(strlen($locale) >= 5);
        $iso2Letter = substr($locale, 0, 2);
        return AppLocale::get3LetterFrom2LetterIsoLanguage($iso2Letter);
    }

    /**
     * Translate an ISO639-2b compatible 3-letter string
     * into the PKP locale identifier.
     *
     * This can be ambiguous if several locales are defined
     * for the same language. In this case we'll use the
     * primary locale to disambiguate.
     *
     * If that still doesn't determine a unique locale then
     * we'll choose the first locale found.
     *
     * @return string
     */
    public static function getLocaleFrom3LetterIso($iso3Letter)
    {
        assert(strlen($iso3Letter) == 3);
        $primaryLocale = AppLocale::getPrimaryLocale();

        $localeCandidates = [];
        $locales = & AppLocale::_getAllLocalesCacheContent();
        foreach ($locales as $locale => $localeData) {
            assert(isset($localeData['iso639-2b']));
            if ($localeData['iso639-2b'] == $iso3Letter) {
                if ($locale == $primaryLocale) {
                    // In case of ambiguity the primary locale
                    // overrides all other options so we're done.
                    return $primaryLocale;
                }
                $localeCandidates[] = $locale;
            }
        }

        // Return null if we found no candidate locale.
        if (empty($localeCandidates)) {
            return null;
        }

        if (count($localeCandidates) > 1) {
            // Check whether one of the candidate locales
            // is a supported locale. If so choose the first
            // supported locale.
            $supportedLocales = AppLocale::getSupportedLocales();
            foreach ($supportedLocales as $supportedLocale => $localeName) {
                if (in_array($supportedLocale, $localeCandidates)) {
                    return $supportedLocale;
                }
            }
        }

        // If there is only one candidate (or if we were
        // unable to disambiguate) then return the unique
        // (first) candidate found.
        return array_shift($localeCandidates);
    }

    /**
     * Translate the ISO 2-letter language string (ISO639-1) into ISO639-3.
     *
     * @param string $iso1
     *
     * @return string the translated string or null if we
     * don't know about the given language.
     */
    public static function getIso3FromIso1($iso1)
    {
        assert(strlen($iso1) == 2);
        $locales = & AppLocale::_getAllLocalesCacheContent();
        foreach ($locales as $locale => $localeData) {
            if (substr($locale, 0, 2) == $iso1) {
                assert(isset($localeData['iso639-3']));
                return $localeData['iso639-3'];
            }
        }
        return null;
    }

    /**
     * Translate the ISO639-3 into ISO639-1.
     *
     * @param string $iso3
     *
     * @return string the translated string or null if we
     * don't know about the given language.
     */
    public static function getIso1FromIso3($iso3)
    {
        assert(strlen($iso3) == 3);
        $locales = & AppLocale::_getAllLocalesCacheContent();
        foreach ($locales as $locale => $localeData) {
            assert(isset($localeData['iso639-3']));
            if ($localeData['iso639-3'] == $iso3) {
                return substr($locale, 0, 2);
            }
        }
        return null;
    }

    /**
     * Translate the PKP locale identifier into an
     * ISO639-3 compatible 3-letter string.
     *
     * @param string $locale
     *
     * @return string
     */
    public static function getIso3FromLocale($locale)
    {
        assert(strlen($locale) >= 5);
        $iso1 = substr($locale, 0, 2);
        return AppLocale::getIso3FromIso1($iso1);
    }

    /**
    * Translate the PKP locale identifier into an
    * ISO639-1 compatible 2-letter string.
    *
    * @param string $locale
    *
    * @return string
    */
    public static function getIso1FromLocale($locale)
    {
        assert(strlen($locale) >= 5);
        return substr($locale, 0, 2);
    }

    /**
     * Translate an ISO639-3 compatible 3-letter string
     * into the PKP locale identifier.
     *
     * This can be ambiguous if several locales are defined
     * for the same language. In this case we'll use the
     * primary locale to disambiguate.
     *
     * If that still doesn't determine a unique locale then
     * we'll choose the first locale found.
     *
     * @param string $iso3
     *
     * @return string
     */
    public static function getLocaleFromIso3($iso3)
    {
        assert(strlen($iso3) == 3);
        $primaryLocale = AppLocale::getPrimaryLocale();

        $localeCandidates = [];
        $locales = & AppLocale::_getAllLocalesCacheContent();
        foreach ($locales as $locale => $localeData) {
            assert(isset($localeData['iso639-3']));
            if ($localeData['iso639-3'] == $iso3) {
                if ($locale == $primaryLocale) {
                    // In case of ambiguity the primary locale
                    // overrides all other options so we're done.
                    return $primaryLocale;
                }
                $localeCandidates[] = $locale;
            }
        }

        // Return null if we found no candidate locale.
        if (empty($localeCandidates)) {
            return null;
        }

        if (count($localeCandidates) > 1) {
            // Check whether one of the candidate locales
            // is a supported locale. If so choose the first
            // supported locale.
            $supportedLocales = AppLocale::getSupportedLocales();
            foreach ($supportedLocales as $supportedLocale => $localeName) {
                if (in_array($supportedLocale, $localeCandidates)) {
                    return $supportedLocale;
                }
            }
        }

        // If there is only one candidate (or if we were
        // unable to disambiguate) then return the unique
        // (first) candidate found.
        return array_shift($localeCandidates);
    }

    //
    // Private helper methods.
    //
    /**
     * Retrieves locale data from the locales cache.
     *
     * @return array
     */
    public static function &_getAllLocalesCacheContent()
    {
        static $contents = false;
        if ($contents === false) {
            $allLocalesCache = & AppLocale::_getAllLocalesCache();
            $contents = $allLocalesCache->getContents();
        }
        return $contents;
    }

    /**
     * Get the cache object for the current list of all locales.
     *
     * @return FileCache
     */
    public static function &_getAllLocalesCache()
    {
        $cache = & Registry::get('allLocalesCache', true, null);
        if ($cache === null) {
            $cacheManager = CacheManager::getManager();
            $cache = $cacheManager->getFileCache(
                'locale',
                'list',
                ['\APP\i18n\AppLocale', '_allLocalesCacheMiss']
            );

            // Check to see if the data is outdated
            $cacheTime = $cache->getCacheTime();
            if ($cacheTime !== null && $cacheTime < filemtime(AppLocale::LOCALE_REGISTRY_FILE)) {
                $cache->flush();
            }
        }
        return $cache;
    }

    /**
     * Create a cache file with locale data.
     *
     * @param CacheManager $cache
     * @param string $id the cache id (not used here, required by the cache manager)
     */
    public static function _allLocalesCacheMiss($cache, $id)
    {
        $allLocales = & Registry::get('allLocales', true, null);
        if ($allLocales === null) {
            // Add a locale load to the debug notes.
            $notes = & Registry::get('system.debug.notes');
            $notes[] = ['debug.notes.localeListLoad', ['localeList' => AppLocale::LOCALE_REGISTRY_FILE]];

            // Reload locale registry file
            $allLocales = AppLocale::loadLocaleList(AppLocale::LOCALE_REGISTRY_FILE);
            asort($allLocales);
            $cache->setEntireCache($allLocales);
        }
        return null;
    }

    /**
     * Get the sites time zone.
     *
     * @return string Time zone
     */
    public static function getTimeZone()
    {
        $timeZone = null;

        // Load the time zone from the configuration file
        if ($timeZoneConfig = Config::getVar('general', 'time_zone')) {
            $timeZoneDAO = DAORegistry::getDAO('TimeZoneDAO');
            $timeZoneList = $timeZoneDAO->getTimeZones();
            foreach ($timeZoneList as $timeZoneKey => $timeZoneName) {
                if (in_array($timeZoneConfig, [$timeZoneKey, $timeZoneName])) {
                    $timeZone = $timeZoneKey;
                    break;
                }
            }
        }

        // Fall back to the time zone set in php.ini
        if (empty($timeZone)) {
            $timeZone = ini_get('date.timezone');
        }

        // Fall back to UTC
        if (empty($timeZone)) {
            $timeZone = 'UTC';
        }

        return $timeZone;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\i18n\PKPLocale', '\PKPLocale');
    // REGISTRY_LOCALE_FILE excluded because of PHPUnit interaction.
    define('MASTER_LOCALE', PKPLocale::MASTER_LOCALE);
}
