function initItemAutocomplete(inputSelector, options = {}) {
    let itemSearchPage = 1;
    let itemSearchHasMore = true;
    let lastSearchTerm = "";
    let itemSearchAccumulatedItems = [];

    const input = inputSelector;

    input.autocomplete({
        minLength: 1,
        source: function(request, response) {
            if (lastSearchTerm !== request.term) {
                itemSearchPage = 1;
                itemSearchHasMore = true;
                itemSearchAccumulatedItems = [];
            }
            lastSearchTerm = request.term;

            $.ajax({
                url: baseURL + '/partner/ajax/item/get-items',
                dataType: "json",
                data: {
                    search: request.term,
                    page: itemSearchPage,
                    warehouse_id: options.warehouse_id || '',
                    party_id: options.party_id || '',
                    request_from: options.request_from || '',
                },
                success: function(data) {
                    // ✅ Adjust to PHP response
                    let items = data.results || [];
                    itemSearchHasMore = data.hasMore || false;

                    if (items.length === 1 && items[0].text === request.term) {
                        input.autocomplete("option", "select").call(input[0], null, { item: items[0] });
                        input.autocomplete("close");
                        input.removeClass("ui-autocomplete-loading");
                    } else {
                        if (itemSearchPage === 1) {
                            itemSearchAccumulatedItems = [...items];
                        } else {
                            const existingIds = new Set(itemSearchAccumulatedItems.map(i => i.id));
                            items.forEach(i => {
                                if (i.id && !existingIds.has(i.id)) {
                                    itemSearchAccumulatedItems.push(i);
                                }
                            });
                        }

                        const displayItems = itemSearchAccumulatedItems.slice();
                        if (itemSearchHasMore) {
                            displayItems.push({ isLoadMore: true });
                        }

                        response(displayItems);
                    }
                }
            });
        },

        focus: function(event, ui) {
            if (ui.item.isLoadMore) return false;
            input.val(ui.item.text); // ✅ use text instead of name
            return false;
        },

        select: function(event, ui) {
            if (ui.item.isLoadMore) {
                event.preventDefault();
                itemSearchPage++;
                input.autocomplete("search", lastSearchTerm);
                return false;
            }
            input.val(ui.item.text); // ✅ use text
            if (typeof options.onSelect === 'function') {
                options.onSelect(ui.item);
            }
            return false;
        },

        open: function() {
            input.autocomplete("widget").off("scroll.autocomplete").on("scroll.autocomplete", function () {
                const $menu = $(this);
                const scrollTop = $menu.scrollTop();
                const scrollHeight = $menu.prop("scrollHeight");
                const clientHeight = $menu.innerHeight();

                if (itemSearchHasMore && scrollTop + clientHeight >= scrollHeight - 10) {
                    itemSearchHasMore = false;
                    itemSearchPage++;
                    input.autocomplete("search", lastSearchTerm);
                }
            });
        },

    }).autocomplete("instance")._renderItem = function(ul, item) {
        if (item.isLoadMore) {
            return $("<li>")
                .attr("style", "padding: 5px; text-align: center; color: #007bff; cursor: pointer;")
                .append("<div>Loading...</div>")
                .appendTo(ul);
        }
        return $("<li>")
            .attr("style", "padding:5px; border-bottom:1px solid #eee;")
            .append(`<div>${item.text || 'N/A'}</div>`) // ✅ show only text
            .appendTo(ul);
    };
}
