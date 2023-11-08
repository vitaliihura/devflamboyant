<script id="script">
    const tags =<?php
        $audiences = [];
        $postTags = wp_get_post_tags(get_the_ID());
        foreach ($postTags as $tag) {
            array_push($audiences, $tag->slug);
        }

        echo json_encode($audiences);
    ?>;

    function initNotixSubscriptionPopup() {
        const actionElement = document.querySelector("<?php echo esc_attr(get_option(Notix::$NOTIX_TAGS_NOTIFY_FEATURE_SUBSCRIBE_ELEMENT_SELECTOR))?>");

        const popup = document.querySelector("#notix-subscribe-popup");
        const popupClose = document.querySelector(".notix-subscribe-popup-close");
        const popupYesButton = document.querySelector(".notix-subscribe-popup-content-body-yes");
        const popupNoButton = document.querySelector(".notix-subscribe-popup-content-body-no");

        actionElement.addEventListener("click", () => {
            popup.style.display = "flex";
        });

        popupClose.addEventListener("click", (event) => {
            event.stopPropagation();
            popup.style.display = "none";
        });

        popupYesButton.addEventListener("click", (event) => {
            event.stopPropagation();
            popup.style.display = "none";
            notixTagsSubscribe();
        });

        popupNoButton.addEventListener("click", (event) => {
            event.stopPropagation();
            popup.style.display = "none";
            notixTagsUnsubscribe();
        });

        window.onclick = function(event) {
            if (event.target === popup) {
                popup.style.display = "none";
            }
        }
    }

    document.addEventListener("DOMContentLoaded", initNotixSubscriptionPopup);

    function notixTagsAvailableSubscribeState() {
        const subscribedStorage = window.localStorage.getItem("notixSubscribedTags");

        if (subscribedStorage !== null) {
            const tagsSubscribed = JSON.parse(subscribedStorage);

            for (let index = 0; index < tagsSubscribed.length; index++) {
                if (!tags.includes(tagsSubscribed[index])) {
                    return {
                        state: false,
                        storage: tagsSubscribed
                    };
                }
                return {
                    state: true,
                    storage: tagsSubscribed
                };
            }
        }
        return {
            state: false,
            storage: []
        };
    }

    function notixTagsSubscribe() {
        const state = notixTagsAvailableSubscribeState();

        if (tags && tags.length > 0) {
            let subscrStorage = state.storage ?? [];
            let fetchPromises = [];
            for (let i = 0; i < tags.length; i++) {
                if (subscrStorage.includes(tags[i])) {
                    continue;
                }
                fetchPromises.push(
                fetch("https://notix.io/api/wordpress/audience/add?app=<?php echo esc_attr(get_option(Notix::$NOTIX_APP_ID_SETTINGS_KEY))?>",
                    {
                        method: "POST",
                        mode: "cors",
                        cache: "no-cache",
                        headers: {
                            "Authorization-Token": "<?php echo esc_attr(get_option(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY))?>"
                        },
                        credentials: 'include',
                        body: JSON.stringify({audience:tags[i]})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status && data.status === "audience added") {
                            subscrStorage.push(tags[i]);
                        }
                    })
                )
            }

            Promise.all(fetchPromises).then(() => {
                window.localStorage.setItem("notixSubscribedTags", JSON.stringify(subscrStorage));
            });
        }
    }
    function notixTagsUnsubscribe() {
        const state = notixTagsAvailableSubscribeState();
        if (tags && tags.length > 0) {
            let subscrStorage = state.storage ?? [];
            let fetchPromises = [];
            for (let i = 0; i < tags.length; i++) {
                fetchPromises.push(
                fetch("https://notix.io/api/wordpress/audience/del?app=<?php echo esc_attr(get_option(Notix::$NOTIX_APP_ID_SETTINGS_KEY))?>",
                    {
                        method: "POST",
                        mode: "cors",
                        cache: "no-cache",
                        headers: {
                            "Authorization-Token": "<?php echo esc_attr(get_option(Notix::$NOTIX_API_TOKEN_SETTINGS_KEY))?>"
                        },
                        credentials: 'include',
                        body: JSON.stringify({audience:tags[i]})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status && (data.status === "audience removed" || data.status === "audience not found")) {
                            if (subscrStorage.includes(tags[i])) {
                                subscrStorage.splice(subscrStorage.indexOf(tags[i]), 1);
                            }
                        }
                    })
                );
            }

            Promise.all(fetchPromises).then(() => {
                window.localStorage.setItem("notixSubscribedTags", JSON.stringify(subscrStorage));
            });
        }
    }

</script>