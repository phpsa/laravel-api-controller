# [7.1.0](https://git.customd.com/composer/laravel-api-controller/compare/v7.0.1...v7.1.0) (2023-10-31)


### Bug Fixes

* load is slow on larger data-sets, unsetting and reloading the relationship manually is a lot faster. ([afae9c6](https://git.customd.com/composer/laravel-api-controller/commit/afae9c69941438d4ca723a7fca88255e1aea4204))


### Features

* add apiAddFields method ([cd10f4a](https://git.customd.com/composer/laravel-api-controller/commit/cd10f4ae7d27835ec673db0d82e3947041e4a2c4))
* add request macro to force include relations ([96d0af0](https://git.customd.com/composer/laravel-api-controller/commit/96d0af09135b61435dde2b9f85e0aae9f54fe02d))
* allow setting only validated ([e2958b8](https://git.customd.com/composer/laravel-api-controller/commit/e2958b899b241b1d44eac642f6bf2d4e99acf30d))

## [7.0.2](https://git.customd.com/composer/laravel-api-controller/compare/v7.0.1...v7.0.2) (2023-08-15)


### Bug Fixes

* load is slow on larger data-sets, unsetting and reloading the relationship manually is a lot faster. ([afae9c6](https://git.customd.com/composer/laravel-api-controller/commit/afae9c69941438d4ca723a7fca88255e1aea4204))

## [5.9.4](https://git.customd.com/composer/laravel-api-controller/compare/v5.9.3...v5.9.4) (2023-03-06)


### Bug Fixes

* laravel 10 ([27c66cd](https://git.customd.com/composer/laravel-api-controller/commit/27c66cd0e28141bd1cc06325e4718e9aee3985aa))

## [5.9.3](https://git.customd.com/composer/laravel-api-controller/compare/v5.9.2...v5.9.3) (2023-01-10)


### Bug Fixes

* improve hasOne futher ([573de6f](https://git.customd.com/composer/laravel-api-controller/commit/573de6f031bc0de8a2c3d60e32a90c846efd7538))

## [5.9.2](https://git.customd.com/composer/laravel-api-controller/compare/v5.9.1...v5.9.2) (2023-01-09)


### Bug Fixes

* for new hasOne Relations created, where withDefault is set, use the defaults ([a439e24](https://git.customd.com/composer/laravel-api-controller/commit/a439e24ce984458bc41844f849fccf7b7ca2a6f2))

## [5.9.1](https://git.customd.com/composer/laravel-api-controller/compare/v5.9.0...v5.9.1) (2022-12-21)


### Bug Fixes

* morph relation not updated correctly ([8d55c99](https://git.customd.com/composer/laravel-api-controller/commit/8d55c992da547865ad9db25c578c7228f2fc1491))

# [5.9.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.8.4...v5.9.0) (2022-12-21)


### Features

* query parser trait can be used for custom scope ([99b1cfd](https://git.customd.com/composer/laravel-api-controller/commit/99b1cfd4bd7bb7f39402c1eb7f7f51a60bdf546a))

## [5.8.4](https://git.customd.com/composer/laravel-api-controller/compare/v5.8.3...v5.8.4) (2022-12-20)


### Bug Fixes

* getBuilder should call getNewQuery by default if not set ([da058a9](https://git.customd.com/composer/laravel-api-controller/commit/da058a972b9b132a57fc0db2ee93c38dac50743d))

## [5.8.3](https://git.customd.com/composer/laravel-api-controller/compare/v5.8.2...v5.8.3) (2022-12-20)


### Bug Fixes

* patch ability to call include method on model that is not a relationshiop ([8cdba5c](https://git.customd.com/composer/laravel-api-controller/commit/8cdba5c923384bb252613631c8e9cc1823b96d3c))

## [5.8.2](https://git.customd.com/composer/laravel-api-controller/compare/v5.8.1...v5.8.2) (2022-12-14)


### Bug Fixes

* alternate for where Has notHas ([4043407](https://git.customd.com/composer/laravel-api-controller/commit/4043407c70a6e1fbe554f726673998c5241d5205))

# [5.8.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.7.0...v5.8.0) (2022-12-14)


### Bug Fixes

* added has / not_has to filters experimental area ([ab66270](https://git.customd.com/composer/laravel-api-controller/commit/ab662700c9c000ac31a394e97df82745b7dccb92))


### Features

* experimental new filtering engine ([1b109df](https://git.customd.com/composer/laravel-api-controller/commit/1b109df4bdee37568418b4b98530efeebb69c3d2))

# [5.8.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.7.0...v5.8.0) (2022-12-14)


### Features

* experimental new filtering engine ([1b109df](https://git.customd.com/composer/laravel-api-controller/commit/1b109df4bdee37568418b4b98530efeebb69c3d2))

# [5.7.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.6.0...v5.7.0) (2022-12-05)


### Bug Fixes

* **nested:** fixes automatic filtering on hasOne ([d876fc9](https://git.customd.com/composer/laravel-api-controller/commit/d876fc90b7f02685192d48c9aef6f27068bb8449))


### Features

* add restore route for soft deletes ([650a66a](https://git.customd.com/composer/laravel-api-controller/commit/650a66a24f11efe8effe7dc82b244ec183c552ec))

## [5.6.1](https://git.customd.com/composer/laravel-api-controller/compare/v5.6.0...v5.6.1) (2022-10-13)


### Bug Fixes

* **nested:** fixes automatic filtering on hasOne ([d876fc9](https://git.customd.com/composer/laravel-api-controller/commit/d876fc90b7f02685192d48c9aef6f27068bb8449))

# [5.6.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.5.1...v5.6.0) (2022-10-13)


### Bug Fixes

* policy mapping for parent ([c3525f2](https://git.customd.com/composer/laravel-api-controller/commit/c3525f2112940a006e7f411fed414a006a706d00))


### Features

* revoke overriding policies as against core Laravel logic ([1d469c4](https://git.customd.com/composer/laravel-api-controller/commit/1d469c4988458aea4efaf3f223a7200081f08f56))

## [5.5.1](https://git.customd.com/composer/laravel-api-controller/compare/v5.5.0...v5.5.1) (2022-10-11)


### Bug Fixes

* parent policy mapping ([a7101a9](https://git.customd.com/composer/laravel-api-controller/commit/a7101a96f8f30437898ef1c8b00ece28555c64fe))

# [5.5.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.4.1...v5.5.0) (2022-10-11)


### Features

* add ability to use custom policy on a controller ([25ac102](https://git.customd.com/composer/laravel-api-controller/commit/25ac1027207c2d25e9746706360ddf6068bf99f6))

## [5.4.1](https://git.customd.com/composer/laravel-api-controller/compare/v5.4.0...v5.4.1) (2022-09-27)


### Bug Fixes

* relation needs resource available ([9d0ec0b](https://git.customd.com/composer/laravel-api-controller/commit/9d0ec0b93cdca45c91ef77d6e302de59d779ca5a))

# [5.4.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.3.1...v5.4.0) (2022-09-13)


### Features

* **events:** add new trait wrap events ([84b0ed7](https://git.customd.com/composer/laravel-api-controller/commit/84b0ed7e6f2c417b6c370d40c77ea34c20bf8e45))

## [5.3.1](https://git.customd.com/composer/laravel-api-controller/compare/v5.3.0...v5.3.1) (2022-08-06)


### Bug Fixes

* **stubs:** fix policy stub having hardcoded namespace and model for some functions ([f58d5d3](https://git.customd.com/composer/laravel-api-controller/commit/f58d5d3b3faa0cb088aa5fe03c4f0f6dacc4de7b))

# [5.2.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.1.0...v5.2.0) (2022-08-06)


### Bug Fixes

* hidden fields should be hidden by default unless allowed ([0bd653a](https://git.customd.com/composer/laravel-api-controller/commit/0bd653a953266439c0a6cce6064a0870fefbc95c))
* morphMany in the api ([b38d1da](https://git.customd.com/composer/laravel-api-controller/commit/b38d1da11f47bd8451f01b8e3b28d1901a06cd05))
* unused param ([1801a68](https://git.customd.com/composer/laravel-api-controller/commit/1801a68bd714ca6d5a360718970b1f8fdacfaac4))


### Features

* allow forcing selection of columns not for display ([01b31b8](https://git.customd.com/composer/laravel-api-controller/commit/01b31b8dfb43aa601ae4ef182a09e3ba13a5f4cf))

# [5.2.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.1.0...v5.2.0) (2022-07-28)


### Bug Fixes

* hidden fields should be hidden by default unless allowed ([0bd653a](https://git.customd.com/composer/laravel-api-controller/commit/0bd653a953266439c0a6cce6064a0870fefbc95c))


### Features

* allow forcing selection of columns not for display ([01b31b8](https://git.customd.com/composer/laravel-api-controller/commit/01b31b8dfb43aa601ae4ef182a09e3ba13a5f4cf))

# [5.2.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.1.0...v5.2.0) (2022-07-14)


### Features

* allow forcing selection of columns not for display ([01b31b8](https://git.customd.com/composer/laravel-api-controller/commit/01b31b8dfb43aa601ae4ef182a09e3ba13a5f4cf))

# [5.1.0](https://git.customd.com/composer/laravel-api-controller/compare/v5.0.0...v5.1.0) (2022-06-10)


### Bug Fixes

* use model page size definition by default ([93fa9b6](https://git.customd.com/composer/laravel-api-controller/commit/93fa9b6cda7b4fd4e3c45c01378ba4c228ae7258))


### Features

* use model defaults for pagination size unless overwritten ([669ba7d](https://git.customd.com/composer/laravel-api-controller/commit/669ba7df511c7c24041d5f2e151949cd5fb89bbb))

# [4.3.0](https://git.customd.com/composer/laravel-api-controller/compare/v4.2.2...v4.3.0) (2022-06-07)


### Bug Fixes

* documentation ([ffa8678](https://git.customd.com/composer/laravel-api-controller/commit/ffa8678b161e0e544433977b01f258279c6e9753))
* fix whitespace formatting ([b48c638](https://git.customd.com/composer/laravel-api-controller/commit/b48c638f629dcfbbd1d67bcb254f48c3191b1a24))
* update namespace for batch actions ([c41ac90](https://git.customd.com/composer/laravel-api-controller/commit/c41ac90f9f13e2b8ee4632c440838a26c45a25e6))


### Features

* allow usage of Laravel Route Binding ([2cde22e](https://git.customd.com/composer/laravel-api-controller/commit/2cde22ee79ea32e454e6473ac9ad22b03f9f0733))
* Included resources should not inherit parent ([a6e11d3](https://git.customd.com/composer/laravel-api-controller/commit/a6e11d3fe2902d382a9d98956d5a23a52a2a1a03))

# [4.3.0](https://git.customd.com/composer/laravel-api-controller/compare/v4.2.2...v4.3.0) (2022-06-05)


### Bug Fixes

* fix whitespace formatting ([b48c638](https://git.customd.com/composer/laravel-api-controller/commit/b48c638f629dcfbbd1d67bcb254f48c3191b1a24))
* update namespace for batch actions ([c41ac90](https://git.customd.com/composer/laravel-api-controller/commit/c41ac90f9f13e2b8ee4632c440838a26c45a25e6))


### Features

* allow usage of Laravel Route Binding ([2cde22e](https://git.customd.com/composer/laravel-api-controller/commit/2cde22ee79ea32e454e6473ac9ad22b03f9f0733))

# [4.3.0](https://git.customd.com/composer/laravel-api-controller/compare/v4.2.2...v4.3.0) (2022-06-01)


### Bug Fixes

* fix whitespace formatting ([b48c638](https://git.customd.com/composer/laravel-api-controller/commit/b48c638f629dcfbbd1d67bcb254f48c3191b1a24))
* update namespace for batch actions ([c41ac90](https://git.customd.com/composer/laravel-api-controller/commit/c41ac90f9f13e2b8ee4632c440838a26c45a25e6))


### Features

* allow usage of Laravel Route Binding ([2cde22e](https://git.customd.com/composer/laravel-api-controller/commit/2cde22ee79ea32e454e6473ac9ad22b03f9f0733))

# [4.3.0](https://git.customd.com/composer/laravel-api-controller/compare/v4.2.2...v4.3.0) (2022-05-24)


### Bug Fixes

* update namespace for batch actions ([c41ac90](https://git.customd.com/composer/laravel-api-controller/commit/c41ac90f9f13e2b8ee4632c440838a26c45a25e6))


### Features

* allow usage of Laravel Route Binding ([2cde22e](https://git.customd.com/composer/laravel-api-controller/commit/2cde22ee79ea32e454e6473ac9ad22b03f9f0733))

## [4.2.3](https://git.customd.com/composer/laravel-api-controller/compare/v4.2.2...v4.2.3) (2022-05-23)


### Bug Fixes

* update namespace for batch actions ([c41ac90](https://git.customd.com/composer/laravel-api-controller/commit/c41ac90f9f13e2b8ee4632c440838a26c45a25e6))

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
