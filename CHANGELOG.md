
# Changelog

Module: `owebia/magento2-module-shared-php-config`

### 6.1.0 (22 Aug, 2024)
- fix compatibility with magento 2.4.7-p1
- add support for nikic/php-parser ^5.0.0
- drop support for nikic/php-parser <4.18

### 6.0.3 (24 Apr, 2024)
- add support for PHP 8.3 & PHP 8.4
- fix PHP 8.4 compatibility warnings: Implicitly marking a parameter as nullable is deprecated since PHP 8.4
- fix Magento2 coding standard warnings: Comment block is missing

### 6.0.2 (02 Aug, 2023)
- fix TypeError: Owebia\SharedPhpConfig\Model\Wrapper\ArrayWrapper::loadData(): Argument #1 ($key) must be of type string, int given

### 6.0.1 (26 May, 2023)
- add support for PHP 8.2
- ⚠️ breaking changes:
  - drop support for PHP < 7.4
  - drop support for Magento < 2.2
  - internal classes refactored
- ✨ new api:
  - `Api\FunctionProviderInterface`
  - `Api\FunctionProviderPoolInterface`
  - `Api\ParserContextInterface`
  - `Api\ParserInterface`
  - `Api\RegistryInterface`
  - `Api\RequiresParserContextInterface`
- improve code quality:
  - add php doc
  - type enforced
  - use modern syntax
  - reduce class dependencies

### 3.0.8 (28 Apr, 2023)
- add support for PHP 8.2

### 3.0.7 (15 Apr, 2022)
- apply Magento2 coding standard

### 3.0.6 (15 Apr, 2022)
- apply PSR-12 coding standard
- use `bool` instead of `boolean` in PHPDoc
- fix constant declaration and usage
- fix signature mismatch errors while keeping backward compatibility
- drop support for PHP versions 5.5, 5.6 & 7.0
- add support for PHP versions 8.0 & 8.1
- fix phpunit compatibility

### 3.0.5 (17 Sep, 2021)
- use `present` as copyright ending year

### 3.0.4 (14 May, 2021)
- add changelog

### 3.0.3 (09 Sep, 2020)
- fix misspelled variables

### 3.0.2 (09 Sep, 2020)
- fix [#84](https://github.com/owebia/magento2-module-advanced-shipping/issues/84): category name retrieval issue

### 3.0.1 (20 Aug, 2020)
- add Magento 2.4.0 & PHP 7.4 support

### 3.0.0 (22 May, 2020)
- add experimental subtotal incl and excl tax calculation
- remove the copyright year from file headers
- improve translations
- improve code quality
- fix [#78](https://github.com/owebia/magento2-module-advanced-shipping/issues/78): can't access to `$app` methods
- rename module from `Owebia_AdvancedSettingCore` to `Owebia_SharedPhpConfig`
