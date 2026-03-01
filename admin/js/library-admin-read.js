/* global wpApiSettings, pagenow, typenow, adminpage */

jQuery(async () => {
	if ("book" !== typenow || "edit-php" !== adminpage || "edit-book" !== pagenow) {
		return;
	}

	const checkboxes = document.querySelectorAll(".js-library-checkbox");
	const readPercentageEl = document.querySelector(".js-library-read-percentage");

	if (!readPercentageEl) {
		return;
	}

	const { root, versionString, nonce } = wpApiSettings;

	const fetchLibrary = (path, options = {}) => {
		const url = `${root}library/v1/${path}`;
		return fetch(url, {
			credentials: "same-origin",
			headers: {
				"X-WP-Nonce": nonce,
				"Content-Type": "application/json",
			},
			...options,
		});
	};

	const fetchWpApi = (route, body = {}, method = "POST") => {
		const opts = {
			method,
			credentials: "same-origin",
			headers: {
				"X-WP-Nonce": nonce,
				"Content-Type": "application/json",
			},
		};
		if (method.toUpperCase() === "POST" && Object.keys(body).length > 0) {
			opts.body = JSON.stringify(body);
		}
		return fetch(`${root}${versionString}${route}`, opts);
	};

	const getReadingStats = async () => {
		const res = await fetchLibrary("books/reading-stats");
		if (!res.ok) {
			throw new Error("Failed to load reading stats");
		}
		return res.json();
	};

	const saveReadingPercentage = (value) => {
		fetchLibrary("settings/reading_percentage", {
			method: "POST",
			body: JSON.stringify({ reading_percentage: value }),
		});
	};

	const updateUI = (read, total) => {
		const pct = total > 0 ? Math.trunc((read / total) * 100) : 0;
		readPercentageEl.textContent = `${pct}% read`;
		saveReadingPercentage(pct);
	};

	const handleChange = async (evt) => {
		const target = evt.target;
		const id = target.dataset.postId;
		if (!id) return;

		target.disabled = true;
		readPercentageEl.textContent = readPercentageEl.getAttribute("data-loading-text") || "…";

		try {
			const res = await fetchWpApi(`books/${id}`, { read: target.checked }, "POST");
			if (!res.ok) {
				throw new Error("Failed to update");
			}
			const stats = await getReadingStats();
			updateUI(stats.read, stats.total);
		} catch (err) {
			readPercentageEl.textContent = "—";
		} finally {
			target.disabled = false;
		}
	};

	checkboxes.forEach((cb) => cb.addEventListener("change", handleChange));

	if (readPercentageEl.classList.contains("js-library-read-percentage-init")) {
		try {
			const stats = await getReadingStats();
			readPercentageEl.classList.remove("js-library-read-percentage-init");
			updateUI(stats.read, stats.total);
		} catch (err) {
			readPercentageEl.textContent = "—";
		}
	}
});
