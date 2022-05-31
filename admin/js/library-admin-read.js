/* global wpApiSettings, pagenow, typenow, adminpage */

jQuery(async () => {
	console.log({ pagenow, typenow, adminpage });

	if ("book" === typenow && "edit-php" === adminpage && "edit-book" === pagenow) {
		const checkboxes = [...document.querySelectorAll(".js-library-checkbox")];
		const $readPercentage = document.querySelector(".js-library-read-percentage");

		const fetchApi = function (route = "posts", body = {}, method = "POST") {
			console.info("fetchApi", route, body, method);

			const { root, versionString, nonce } = wpApiSettings;
			const options = {
				method,
				credentials: "same-origin",
				headers: {
					"X-WP-Nonce": nonce,
					"Content-Type": "application/json",
				},
			};

			if ("POST" === method.toUpperCase() && Object.keys(body).length !== 0) {
				options.body = JSON.stringify(body);
			}

			return fetch(`${root}${versionString}${route}`, options);
		};

		const updateReadingPercentage = (value) => {
			console.info("updateReadingPercentage", value);

			const { root, nonce } = wpApiSettings;

			fetch(`${root}library/v1/settings/reading_percentage`, {
				method: "POST",
				credentials: "same-origin",
				body: JSON.stringify({
					reading_percentage: value,
				}),
				headers: {
					"X-WP-Nonce": nonce,
					"Content-Type": "application/json",
				},
			});
		};

		const getRead = async () => {
			console.info("getRead");

			let page = 1;

			const response = await fetchApi(`books?page=${page}`, {}, "GET");
			const data = await response.json();
			const results = data;

			while (
				response.headers.get("X-WP-TotalPages") &&
				response.headers.get("X-WP-TotalPages") > page
			) {
				page++;

				const response = await fetchApi(`books?page=${page}`, {}, "GET");
				const data = await response.json();

				results.push(...data);
			}

			console.log(results);

			const read = results.filter((r) => r.read).length;

			return {
				read,
				total: results.length,
			};
		};

		const handleChange = async ({ target }) => {
			console.log("handleChange");

			const { checked } = target;
			const { postId: id } = target.dataset;

			target.disabled = true;
			$readPercentage.innerHTML = $readPercentage.getAttribute("data-loading-text");

			const response = await fetchApi(`books/${id}`, { read: checked }, "POST");

			console.log(response);

			target.disabled = false;

			const { read, total } = await getRead();

			$readPercentage.innerHTML = `${Math.trunc((read / total) * 100)}% read`;
			updateReadingPercentage(Math.trunc((read / total) * 100));
		};

		checkboxes.forEach(($checkbox) => $checkbox.addEventListener("change", handleChange));

		if ($readPercentage.classList.contains("js-library-read-percentage-init")) {
			const { read, total } = await getRead();

			$readPercentage.classList.remove("js-library-read-percentage-init");
			$readPercentage.innerHTML = `${Math.trunc((read / total) * 100)}% read`;

			updateReadingPercentage(Math.trunc((read / total) * 100));
		}
	}
});
