/*! CSSelector.js - v0.1.0 - 2012-10-22
* https://github.com/stevoland/CSSelector.js
* Copyright (c) 2012 stevo (Stephen Collings); Licensed MIT */

function CSSelector (el) {
		var names = [];
		while (el.parentNode) {
			if (el.id) {
				names.unshift('#' + el.id);
				break;
			} else {
				if (el === el.ownerDocument.documentElement || el === el.ownerDocument.body) {
					names.unshift(el.tagName);
				} else {
					for (var c = 1, e = el; e.previousElementSibling; e = e.previousElementSibling, c++) {}
					names.unshift((el.tagName ? el.tagName : '*') + ':nth-child(' + c + ')');
				}
				el = el.parentNode;
			}
		}
		return names.join(' > ');
	}
