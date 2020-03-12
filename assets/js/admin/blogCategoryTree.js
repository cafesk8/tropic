import Register from 'framework/common/utils/Register';

export default function initBlogCategoryTree () {
    $('.js-blog-category-tree.js-category-tree-form-item-icon').click();
}

(new Register()).registerCallback(initBlogCategoryTree);
