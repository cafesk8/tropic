import 'jquery-ui-nested-sortable';
import CategoryTreeSorting from 'framework/admin/components/CategoryTreeSorting';

// not working at the moment, it is not possible to monkeypatch the constructor :(
const myConstructor = ($rootTree, $saveButton) => {
    this.$rootTree = $rootTree;
    this.$saveButton = $saveButton;
    this.protectRoot = $rootTree.hasClass('js-protect-root');

    const _this = this;
    this.$rootTree.nestedSortable({
        listType: 'ul',
        handle: '.js-category-tree-item-handle',
        items: '.js-category-tree-item',
        placeholder: 'js-category-tree-placeholder form-tree__placeholder',
        toleranceElement: '> .js-category-tree-item-line',
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.6,
        revert: 100,
        change: () => _this.onChange(),
        protectRoot: _this.protectRoot
    });

    $saveButton.click(() => this.onSaveClick());
};

CategoryTreeSorting.constructor = myConstructor;
