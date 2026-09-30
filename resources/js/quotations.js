import $ from 'jquery';

$(function () {
    /*
     * Quotation show — Product Specification selection
     */
    const $showPage = $('#quotation-show-page');
    const $specification = $('#quotation-product-specification');

    if (!$showPage.length || !$specification.length) {
        return;
    }

    const $itemName = $('#quotation-item-name');
    const $unit = $('#quotation-item-unit');
    const $unitPrice = $('#quotation-item-unit-price');
    const $description = $('#quotation-item-description');

    function resetItemFields() {
        $itemName.val('');
        $unit.val('piece');
        $unitPrice.val('');
        $description.val('');
    }

    function populateSpecification(specification) {
        $itemName.val(specification.item_name || '');
        $unit.val(specification.unit || 'piece');
        $unitPrice.val(specification.unit_price || '');
        $description.val(specification.description || '');
    }

    function loadSpecifications() {
        const organizationId = $showPage.data('organization-id');
        const baseUrl = $showPage.data('options-url');

        if (!organizationId || !baseUrl) {
            return;
        }

        $specification
            .prop('disabled', true)
            .empty()
            .append(
                $('<option>', {
                    value: '',
                    text: 'Loading Product Specifications...',
                })
            );

        $.ajax({
            url: `${baseUrl}/${organizationId}`,
            method: 'GET',
            dataType: 'json',
        })
            .done(function (data) {
                $specification.empty().append(
                    $('<option>', {
                        value: '',
                        text: 'Custom item',
                    })
                );

                data.product_specifications.forEach(function (specification) {
                    $specification.append(
                        $('<option>', {
                            value: specification.id,
                            text:
                                `${specification.item_name} — ` +
                                `${specification.unit} — ` +
                                `₦${Number(specification.unit_price).toLocaleString('en-NG', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                })}`,
                        }).data('specification', specification)
                    );
                });

                $specification.prop('disabled', false);
            })
            .fail(function () {
                $specification
                    .empty()
                    .append(
                        $('<option>', {
                            value: '',
                            text: 'Unable to load specifications',
                        })
                    )
                    .prop('disabled', true);

                window.alert(
                    'The organization Product Specifications could not be loaded. Please try again.'
                );
            });
    }

    $specification.on('change', function () {
        const specification = $(this)
            .find('option:selected')
            .data('specification');

        if (!specification) {
            resetItemFields();
            return;
        }

        populateSpecification(specification);
    });

    loadSpecifications();
});

$(function () {
    const $createForm = $('#quotation-create-form');

    if (!$createForm.length) {
        return;
    }

    const currency = function (value) {
        return `₦${Number(value).toLocaleString('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    };

    function calculateCreateTotals() {
        let subtotal = 0;

        $createForm.find('.quotation-specification-row').each(function () {
            const $row = $(this);
            const unitPrice = Number($row.data('unit-price')) || 0;
            const quantity = Number(
                $row.find('.quotation-specification-quantity').val()
            ) || 0;

            const lineTotal = $row
                .find('.quotation-specification-checkbox')
                .is(':checked')
                ? unitPrice * quantity
                : 0;

            $row.find('.quotation-specification-line-total').text(
                currency(lineTotal)
            );

            subtotal += lineTotal;
        });

        $('#quotation-create-estimated-subtotal').text(currency(subtotal));
    }

    $createForm.on(
        'change input',
        '.quotation-specification-checkbox, .quotation-specification-quantity',
        calculateCreateTotals
    );

    calculateCreateTotals();
});
