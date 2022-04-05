/**
 * WC Show Single Variations
 * Braintum
 *
 * Copyright (c) 2018 Braintum
 * Licensed under the GPLv2+ license.
 */

/*jslint browser: true */
/*global jQuery:false */

jQuery(document).ready(function ($, window, document, undefined) {
    'use strict';
    $.wc_show_single_variation_admin = {
        init: function () {
            $('#wc_single_variation_tab_exclude_categories').on('change', this.selectIncludeCategories);
            $('#wc_single_variation_tab_include_categories').on('change', this.selectExcludedCategories);
            $("#wc_single_variation_tab_exclude_categories option[value='all']").each(function () {
                $(this).remove();
            });
        },
        selectIncludeCategories: function () {
            var selectedData = $(this).val();
            if (selectedData !== '') {
                $(this).parents('.form-table').find('#wc_single_variation_tab_include_categories').val('all');

            }
        },
        selectExcludedCategories: function () {
            var selectedData = $(this).val();
            if (selectedData !== '' || selectedData !== 'all') {
                $(this).parents('.form-table').find('#wc_single_variation_tab_exclude_categories').val('');
            }
        }
    };
    $.wc_show_single_variation_admin.init();
});