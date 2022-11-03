<?php
namespace Ropi\ContentEditor\Service;

class ContentDocumentNodeService implements ContentDocumentNodeServiceInterface
{

    public function mergeConfiguration(array $targetNode, array $sourceNode, bool $keepLanguageSpecificSettings = false): array
    {
        static $rootSourceNode;
        $isRoot = false;

        if (!is_array($rootSourceNode)) {
            $rootSourceNode = $sourceNode;
            $isRoot = true;
        }

        if ($this->compareUuid($targetNode, $sourceNode)) {
            $targetConfigurationBefore = $targetNode['configuration'];

            if (isset($sourceNode['configuration']) && is_array($sourceNode['configuration'])) {
                $targetNode['configuration'] = $sourceNode['configuration'];
            } else {
                $targetNode['configuration'] = [];
            }

            if ($keepLanguageSpecificSettings && is_array($targetConfigurationBefore)) {
                $configPathsToKeep = isset($sourceNode['meta']['languageSpecificSettings'])
                                     && is_array($sourceNode['meta']['languageSpecificSettings'])
                                     ? $sourceNode['meta']['languageSpecificSettings']
                                     : [];

                foreach ($configPathsToKeep as $configPath) {
                    $targetValue = $this->getArrayValueByPath($targetConfigurationBefore, $configPath);
                    $this->setArrayValueByPath($targetNode['configuration'], $configPath, $targetValue);
                }
            }
        }

        if ($this->hasChildren($targetNode)) {
            foreach ($targetNode['children'] as $key => &$targetChildNode) {
                $foundNode = null;

                if ($targetChildNode['type'] === 'element') {
                    $foundNode = $this->searchElement($rootSourceNode, $targetChildNode['meta']['uuid'], true);
                } elseif ($targetChildNode['type'] === 'area') {
                    $foundNode = $this->searchArea($sourceNode, $targetChildNode['meta']['name'], false);
                }

                if (!is_array($foundNode)) {
                    continue;
                }

                $targetChildNode = $this->mergeConfiguration($targetChildNode, $foundNode, $keepLanguageSpecificSettings);
            }
        }

        if ($isRoot) {
            $rootSourceNode = null;
        }

        return $targetNode;
    }

    public function mergeContents(array $targetNode, array $sourceNode): array
    {
        static $rootSourceNode;
        $isRoot = false;

        if (!is_array($rootSourceNode)) {
            $rootSourceNode = $sourceNode;
            $isRoot = true;
        }

        if ($this->compareUuid($targetNode, $sourceNode)) {
            if (isset($sourceNode['contents']) && is_array($sourceNode['contents'])) {
                $targetNode['contents'] = $sourceNode['contents'];
            } else {
                $targetNode['contents'] = [];
            }
        }

        if ($this->hasChildren($targetNode)) {
            foreach ($targetNode['children'] as $key => &$targetChildNode) {
                $foundNode = null;

                if ($targetChildNode['type'] === 'element') {
                    $foundNode = $this->searchElement($rootSourceNode, $targetChildNode['meta']['uuid'], true);
                } elseif ($targetChildNode['type'] === 'area') {
                    $foundNode = $this->searchArea($sourceNode, $targetChildNode['meta']['name'], false);
                }

                if (!is_array($foundNode)) {
                    continue;
                }

                $targetChildNode = $this->mergeContents($targetChildNode, $foundNode);
            }
        }

        if ($isRoot) {
            $rootSourceNode = null;
        }

        return $targetNode;
    }

    protected function compareUuid(array $contentNode1, array $contentNode2): bool
    {
        if (!isset($contentNode1['type']) || !isset($contentNode2['type'])) {
            return false;
        }

        if ($contentNode1['type'] !== 'element' || $contentNode2['type'] !== 'element') {
            return false;
        }

        if (!isset($contentNode1['meta']['uuid']) || !isset($contentNode2['meta']['uuid'])) {
            return false;
        }

        return $contentNode1['meta']['uuid'] === $contentNode2['meta']['uuid'];
    }

    protected function &searchArea(array &$contentNode, string $name, bool $recursive = false): ?array
    {
        if (!$this->hasChildren($contentNode)) {
            $result = null;
            return $result;
        }

        foreach ($contentNode['children'] as &$childNode) {
            if ($childNode['type'] === 'area' && $childNode['meta']['name'] === $name) {
                return $childNode;
            }

            if ($recursive) {
                $area = &$this->searchArea($childNode, $name, $recursive);
                if (is_array($area)) {
                    return $area;
                }
            }
        }

        $result = null;
        return $result;
    }

    protected function &searchElement(array &$contentNode, string $id, bool $recursive = false): ?array
    {
        if (!$this->hasChildren($contentNode)) {
            $result = null;
            return $result;
        }

        foreach ($contentNode['children'] as &$childNode) {
            if ($childNode['type'] === 'element' && $childNode['meta']['uuid'] === $id) {
                return $childNode;
            }

            if ($recursive) {
                $element = &$this->searchElement($childNode, $id, $recursive);
                if (is_array($element)) {
                    return $element;
                }
            }
        }

        $result = null;
        return $result;
    }

    protected function hasChildren(array $contentNode): bool
    {
        if (!isset($contentNode['children']) || !is_array($contentNode['children'])) {
            return false;
        }

        return !empty($contentNode);
    }

    /**
     * @param array $array
     * @param string $path
     * @return mixed
     */
    private function getArrayValueByPath(array $array, string $path)
    {
        $segments = explode('.', $path);

        $current = &$array;
        foreach ($segments as $segment) {
            if (!is_array($current) || !isset($current[$segment])) {
                return null;
            }

            $current = &$current[$segment];
        }

        return $current;
    }

    /**
     * @param array &$array
     * @param string $path
     * @param mixed $value
     * @return void
     */
    private function setArrayValueByPath(array &$array, string $path, $value): void
    {
        $segments = explode('.', $path);

        $current = &$array;
        foreach ($segments as $segment) {
            if (!is_array($current) || !isset($current[$segment])) {
                return;
            }

            $current = &$current[$segment];
        }

        $current = $value;
    }
}
