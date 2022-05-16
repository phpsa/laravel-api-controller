## [4.2.2](https://git.customd.com/composer/laravel-api-controller/compare/v4.2.1...v4.2.2) (2022-05-16)


### Bug Fixes

* make sure case of keys is correct ([3bc2adc](https://git.customd.com/composer/laravel-api-controller/commit/3bc2adcd4d450d6ab52ac02ccaa1578bda1282fb))

## [4.2.1](https://git.customd.com/composer/laravel-api-controller/compare/v4.2.0...v4.2.1) (2022-05-10)


### Bug Fixes

* Breaking model holding onto incorrect static bindings ([01019e8](https://git.customd.com/composer/laravel-api-controller/commit/01019e8741865a65da9fea414853e9e27451c05b))

# [4.2.0](https://git.customd.com/composer/laravel-api-controller/compare/v4.1.1...v4.2.0) (2022-05-05)


### Bug Fixes

* builder should be initialised ([e0de920](https://git.customd.com/composer/laravel-api-controller/commit/e0de9205bc9274dd36b7612528a24320cddf7459))


### Features

* upgrade to construct models on first call instead of on controller instansiation ([742b47e](https://git.customd.com/composer/laravel-api-controller/commit/742b47eb7ed7323c2f324e51e05057f78739ffb1))

## [4.1.1](https://git.customd.com/composer/laravel-api-controller/compare/v4.1.0...v4.1.1) (2022-04-26)


### Bug Fixes

* missing pivot option ([102591d](https://git.customd.com/composer/laravel-api-controller/commit/102591d0632822ede30afa506b0dad2d48cc14c6))

# [4.1.0](https://git.customd.com/composer/laravel-api-controller/compare/v4.0.4...v4.1.0) (2022-04-11)


### Features

* extends filterUserField to pass the resource to all fieldGates, so that gates with context-dependent logic can be used. ([dfed357](https://git.customd.com/composer/laravel-api-controller/commit/dfed357bb4eecd2d0fbb7aee0fd470127c9d67ea))

## [4.0.4](https://git.customd.com/composer/laravel-api-controller/compare/v4.0.3...v4.0.4) (2022-04-07)


### Bug Fixes

* resource to Array should be a manual call ([15badb7](https://git.customd.com/composer/laravel-api-controller/commit/15badb79c99c8111478b24a108aa47b572c54b92))

## [4.0.3](https://git.customd.com/composer/laravel-api-controller/compare/v4.0.2...v4.0.3) (2022-03-30)


### Bug Fixes

* api nested stub not working ([6f003c8](https://git.customd.com/composer/laravel-api-controller/commit/6f003c842f889398b5eb18e13184d9e9e619aa5f))
* stub paths / variables ([1f7a787](https://git.customd.com/composer/laravel-api-controller/commit/1f7a787f86881314df15ce82656d610fd78992c0))

## [4.0.2](https://git.customd.com/composer/laravel-api-controller/compare/v4.0.1...v4.0.2) (2022-03-30)


### Bug Fixes

* non-default related models were returned if defined in $mapResources ([d9d4ff1](https://git.customd.com/composer/laravel-api-controller/commit/d9d4ff1f76130c5234d28d0230b633096d891492))

## [4.0.1](https://git.customd.com/composer/laravel-api-controller/compare/v4.0.0...v4.0.1) (2022-03-15)


### Bug Fixes

* array breaks when passed to resource ([2380d81](https://git.customd.com/composer/laravel-api-controller/commit/2380d816e3d13a79c5bd1c13106ba4d312fb8645))

# [4.0.0](https://git.customd.com/composer/laravel-api-controller/compare/v3.2.3...v4.0.0) (2022-03-14)


### Bug Fixes

* lara 9 style atributes ([9df0fb8](https://git.customd.com/composer/laravel-api-controller/commit/9df0fb8553f824d14daccfa9a4d07d23a52764bd))


### chore

* update Docs ([d594689](https://git.customd.com/composer/laravel-api-controller/commit/d59468978d768047f9e0d7c56483e46daf543ba2))


### Features

* Laravel 9 compatability ([d8b8906](https://git.customd.com/composer/laravel-api-controller/commit/d8b890682a16f38acf4f973df8da6a57fcdd39c1))


### BREAKING CHANGES

* - support for laravel prior to 8.5 dropped

# [4.0.0-beta.1](https://git.customd.com/composer/laravel-api-controller/compare/v3.2.3...v4.0.0-beta.1) (2022-03-14)


### Bug Fixes

* parser not initialised ([0d3c36f](https://git.customd.com/composer/laravel-api-controller/commit/0d3c36fe087e136cddf82f3237804755f8acf212))
* use Attribute type attributes ([84e470c](https://git.customd.com/composer/laravel-api-controller/commit/84e470cb227ade00d438c3a07de0adc17e8ca594))


### Features

* Laravel 9 compatability ([e5f51c8](https://git.customd.com/composer/laravel-api-controller/commit/e5f51c8c0038a2f8d9b59204675da083ce4d2fb9))


### BREAKING CHANGES

* No longer supports older than laravel 8.5 / php 8.0

# [4.0.0-beta.1](https://git.customd.com/composer/laravel-api-controller/compare/v3.2.3...v4.0.0-beta.1) (2022-03-10)


### Bug Fixes

* parser not initialised ([0d3c36f](https://git.customd.com/composer/laravel-api-controller/commit/0d3c36fe087e136cddf82f3237804755f8acf212))


### Features

* Laravel 9 compatability ([e5f51c8](https://git.customd.com/composer/laravel-api-controller/commit/e5f51c8c0038a2f8d9b59204675da083ce4d2fb9))


### BREAKING CHANGES

* No longer supports older than laravel 8.5 / php 8.0
