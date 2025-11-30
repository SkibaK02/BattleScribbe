(function ($) {
    function initSlotToggles() {
        $('[data-slot-toggle]').on('click', function () {
            const $card = $(this).closest('[data-slot-card]');
            const $state = $(this).find('.state');
            $card.toggleClass('open');
            if ($state.length) {
                $state.text($card.hasClass('open') ? '-' : '+');
            }
        });
    }

    function initUnitForm() {
        const $root = $('[data-unit-form]');
        if (!$root.length) {
            return;
        }

        const maxSize = Number($root.data('unitMaxSize')) || 1;
        const $unit = $root.find('[data-points-unit]');
        const $total = $root.find('[data-points-total]');
        const baseOtherPoints = Number($root.data('otherPoints')) || 0;
        const $other = $root.find('[data-points-other]');
        const $baseRadios = $root.find('[data-base-cost]');
        const $inputs = $root.find('#unit-options').find('input, select');
        const $restrictedInputs = $root.find('[data-requires-experience]');

        $other.text(baseOtherPoints);
        $total.text(baseOtherPoints);

        function updatePoints() {
            let total = 0;
            $baseRadios.each(function () {
                if ($(this).is(':checked')) {
                    total += Number($(this).data('baseCost')) || 0;
                }
            });

            $inputs.each(function () {
                const $input = $(this);
                if ($input.attr('type') === 'number') {
                    const per = Number($input.data('costPer') || 0);
                    total += per * Number($input.val() || 0);
                } else if ($input.attr('type') === 'checkbox') {
                    if ($input.is(':checked')) {
                        if ($input.data('perModel')) {
                            total += Number($input.data('cost') || 0) * Number($input.data('maxPerModel') || 0);
                        } else {
                            total += Number($input.data('cost') || 0);
                        }
                    }
                } else if ($input.is('select')) {
                    const $selected = $input.find(':selected');
                    total += Number($selected.data('cost') || 0);
                    const perModel = Number($selected.data('costPer') || 0);
                    if (perModel > 0) {
                        total += perModel * maxSize;
                    }
                }
            });

            $unit.text(total);
            $total.text(total + baseOtherPoints);
        }

        $baseRadios.on('change', updatePoints);
        $inputs.on('input change', updatePoints);
        updatePoints();

        function updateOptionVisibility() {
            const currentExp = $baseRadios.filter(':checked').val();
            $restrictedInputs.each(function () {
                const $input = $(this);
                const required = $input.data('requiresExperience');
                const $block = $input.closest('[data-option-block]');

                if (!required || required === currentExp) {
                    $block.show();
                } else {
                    if ($input.attr('type') === 'number') {
                        $input.val(0);
                    }
                    $block.hide();
                }
            });
        }

        $baseRadios.on('change', updateOptionVisibility);
        updateOptionVisibility();
    }

    $(function () {
        initSlotToggles();
        initUnitForm();
    });
})(jQuery);


