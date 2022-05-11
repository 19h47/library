/* global wpApiSettings, pagenow, typenow, adminpage */

jQuery(() => {
    console.log({ pagenow, typenow, adminpage });

    const checkboxes = [...document.querySelectorAll(".js-library-checkbox")];

    const fetchApi = function (route = "posts", data, method = "GET") {
        const { root, versionString, nonce } = wpApiSettings;
        return fetch(`${root}${versionString}${route}`, {
            method,
            credentials: "same-origin",
            body: JSON.stringify(data),
            headers: {
                "X-WP-Nonce": nonce,
                "Content-Type": "application/json",
            },
        });
    };

    const handleChange = async ({ target }) => {
        const { checked } = target;
        const { postId: id } = target.dataset;

        target.disabled = true;

        const response = await fetchApi(`books/${id}`, { read: checked }, "POST");

        console.log(response);

        target.disabled = false;
    };

    checkboxes.forEach(($checkbox) => $checkbox.addEventListener("change", handleChange));
});
