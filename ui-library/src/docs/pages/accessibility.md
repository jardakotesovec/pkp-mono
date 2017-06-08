# Accessibility

Some components require specific markup or special handling based on state to ensure that they are accessible. Component-specific guidelines are outlined within each component's Notes section. The following are broad accessibility guidelines which should be followed.

## Links vs Buttons

The link element, `<a>`, should only be used for actions which navigate away from the current page. When opening modals or performing actions on the same page, use the `<button>` element.

A good rule of thumb is this: if the URL changes when the action is clicked, use `<a>`. Otherwise, use `<button>`.

## Focus state and keyboard-based navigation

Always test by navigating components with a keyboard, using <kbd>TAB</kbd> to cycle through elements. Any HTML elements which are not visible should not receive focus. You should be able to see where the focus is on the page at any time.

For example, a [ListPanelFilter](/components/detail/list-panel--with-filter) is hidden until enabled. When hidden, focusable elements such as the filter buttons should be skipped when navigating with the <kbd>TAB</kbd> key.

Furthermore, all actions should be possible without a mouse. If drag-and-drop features are used, there should be alternative tools for moving items around when only using a keyboard.

### Moving focus

As a general rule, you should *never* move the focus programmatically. This can be disorienting and frustrating for users who rely on keyboard navigation.

We make an exception for this when the user performs an action which indicates a particular focus is desired.

For example, when a button is pressed to open a modal, the focus should be moved into that modal and kept there until the modal is closed. When closed, the focus should be returned to the element which opened the modal.

### Avoid tabindex

Avoid using `tabindex="1"` and `tabindex="2"` to enforce the order of navigation. There are only two occassions when you should use `tabindex`:

1. Use `tabindex="-1"` to prevent an item from receiving focus. You may wish to do this to remove drag-and-drop controls from a keyboard user's tab order, or to prevent the user from having to tab through elements which are currently hidden.
2. Use `tabindex="0"` to allow an item to receive focus when it otherwise would not, such as a `<div>` or `<span>` element. This is useful when you want to move the focus to an element, and want to target a heading or label. (Generally, moving the focus should be avoided. See above.)

## Labelling for those without sight

When a component uses icons or a visual layout to communicate what a button or value represents, you should still provide text labelling for users without sight. You can use the `--screenReader` class to visually hide the label but ensure it's displayed to screen readers.

For example, a search field often uses an icon to indicate it's purpose. This should still be accompanied by a label.

```
<div class="search">
	<span class="fa fa-search"></span>
	<input id="searchInput" placeholder="Search submissions">
	<label for="searchInput" class="--screenReader">
		Search submissions
	</label>
</div>
```

In this case, the `placeholder` attribute can not be read by all screen readers, so a label is provided and then hidden from sighted users.
