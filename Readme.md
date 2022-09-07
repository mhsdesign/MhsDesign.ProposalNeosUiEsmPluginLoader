## MhsDesign.ProposalNeosUiEsmPluginLoader
Allows ES-Module Plugins to be loaded in the Neos.Ui, by allowing `<src type="module" />`

for fully compatibility with [ESM Neos Ui Plugin Builder with Esbuild](https://github.com/mhsdesign/esbuild-neos-ui-extensibility)

### Issue in the Neos.Ui
https://github.com/neos/neos-ui/issues/3097

## Usage
Neos.Neos.Ui.resources.javascript and stylesheets
can make use of the proposed `attributes` array.

```yaml
Neos:
  Neos:
    Ui:
      resources:
        javascript:
          'My.Cool:Plugin':
            resource: 'resource://My.Cool/Public/NeosUserInterface/Plugin.js'
            # legacy first level attribute 'defer'
            # defer: true

            # NEW Attributes!
            attributes:
              type: "module"
              defer: true
              any: "thing"

        stylesheets:
          'My.Cool:Plugin':
            # NEW Attributes!
            attributes:
              any: "thing"
```


## Install

```
composer require mhsdesign/proposal-neos-ui-esm-plugin-loader
```

## Implementation
The Neos.Ui uses a [StyleAndJavascriptInclusionService](https://github.com/neos/neos-ui/blob/744d50cd2503f64af1c1baf78c756269d34f7116/Classes/Domain/Service/StyleAndJavascriptInclusionService.php#L23), which reads the `Neos.Neos.Ui.resources.javascript` and builds html `<src>` tags from it.

This Service is now replaced in Objects.yaml with a version, which allows additional attributes.

([Line 100](https://github.com/neos/neos-ui/blob/744d50cd2503f64af1c1baf78c756269d34f7116/Classes/Domain/Service/StyleAndJavascriptInclusionService.php#L100))
```diff
- $defer = key_exists('defer', $element) && $element['defer'] ? 'defer ' : '';
- $result .= $builderForLine($finalUri, $defer);
+ $additionalAttributes = array_merge(
+     // legacy first level 'defer' attribute
+     isset($element['defer']) ? ['defer' => $element['defer']] : [],
+     $element['attributes'] ?? []
+ );
+ $result .= $builderForLine($finalUri, $this->htmlAttributesArrayToString($additionalAttributes));
```
