<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/10/16
     * Time: 1:15 PM
     */

    namespace TwistersFury\PhpCi\Plugin;

    use PHPCI\Plugin;
    use PHPCI\Builder;
    use PHPCI\Model\Build;

    abstract class AbstractPlugin implements Plugin {
        private $pluginOptions = NULL;
        private $buildModel    = NULL;
        private $ciBuilder     = NULL;

        public function __construct(Builder $phpci, Build $build, array $options = array()) {
            $this->setModel($build)
                 ->setOptions($options)
                 ->setBuilder($phpci);
        }

        public function setModel(Build $buildModel) {
            $this->buildModel = $buildModel;

            return $this;
        }

        public function setOptions(array $pluginOptions) {
            $this->pluginOptions = $pluginOptions;

            return $this;
        }

        public function setBuilder(Builder $ciBuilder) {
            $this->ciBuilder = $ciBuilder;

            return $this;
        }

        public function getModel() {
            return $this->buildModel;
        }

        public function getOptions() {
            return $this->pluginOptions;
        }

        public function getOption($optionName, $defaultValue = NULL) {
            $pluginOptions = $this->getOptions();
            if (isset($pluginOptions[$optionName])) {
                return $pluginOptions[$optionName];
            }

            return $defaultValue;
        }

        /** @return Builder */
        public function getBuilder() {
            return $this->ciBuilder;
        }
    }