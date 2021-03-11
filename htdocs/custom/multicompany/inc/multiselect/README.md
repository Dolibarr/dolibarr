multiselect
===========

jQuery multiselect plugin with two sides. The user can select one or more items and send them to the other side.

# [Demo](http://crlcu.github.com/multiselect/)

## Requirements

- jQuery 1.7 or higher

## Quick start

Several quick start options are available:

- Clone the repo: `git clone https://github.com/crlcu/multiselect.git` or
- Install with [Bower](http://bower.io): `bower install multiselect-two-sides`.

### Usage example

```html
<div class="row">
	<div class="col-xs-5">
		<select name="from[]" id="multiselect" class="form-control" size="8" multiple="multiple">
			<option value="1">Item 1</option>
			<option value="3">Item 3</option>
			<option value="2">Item 2</option>
		</select>
	</div>
	
	<div class="col-xs-2">
		<button type="button" id="multiselect_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>
		<button type="button" id="multiselect_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>
		<button type="button" id="multiselect_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>
		<button type="button" id="multiselect_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>
	</div>
	
	<div class="col-xs-5">
		<select name="to[]" id="multiselect_to" class="form-control" size="8" multiple="multiple"></select>
	</div>
</div>
```

```javascript
<script type="text/javascript" src="path/to/jquery.min.js"></script>
<script type="text/javascript" src="path/to/multiselect.min.js"></script>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#multiselect').multiselect();
});
</script>
```

## Bugs and feature requests

If your problem or idea is not [addressed](https://github.com/crlcu/multiselect/issues) yet, [please open a new issue](https://github.com/crlcu/multiselect/issues/new).

## Versioning

For transparency into release cycle and in striving to maintain backward compatibility, multiselect is maintained under [the Semantic Versioning guidelines](http://semver.org/).


## License

The multiselect plugin is open-sourced software licensed under the [the MIT license](https://github.com/crlcu/multiselect/blob/master/LICENSE).
