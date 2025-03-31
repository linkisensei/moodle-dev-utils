<?php namespace moodle_dev_utils\http\filters\lhs\conditions\traits;

/**
 * Trait wildcard_trait
 *
 * Provides a utility method for normalizing wildcard characters in search filters.
 * Replaces asterisks (*) with percent signs (%) to match SQL LIKE syntax,
 * and escapes other special characters like _ and % to avoid unintended behavior.
 */
trait wildcard_trait {

    /**
     * Converts all asterisks (*) into SQL LIKE wildcards (%) and escapes
     * special characters (_, %, and \) to be treated literally.
     *
     * Example: 'na*me_01%' becomes 'na%me\_01\%' (to be used with LIKE ... ESCAPE '\')
     *
     * @param string|null $value The input string to normalize.
     * @return string|null The normalized string, or null if input is null or empty.
     */
    public function normalize_wildcards(?string $value): ?string {
        if (empty($value)) {
            return $value;
        }

        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(['_', '%'], ['\_', '\%'], $value);
        $value = str_replace('*', '%', $value);

        return $value;
    }
}