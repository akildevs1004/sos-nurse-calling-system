export default ({ $axios, store }, inject) => {
  // const isClient = typeof window !== "undefined";

  // let backendURL = null; //process.env.BACKEND_URL;
  // let appURL = null; //process.env.APP_URL;

  // if (!process.env.BACKEND_URL) {
  //   backendURL = isClient
  //     ? `http://${window.location.hostname || "localhost"}:8000/api`
  //     : "http://localhost:8000/api";
  // }
  // if (!process.env.APP_URL) {
  //   appURL = isClient
  //     ? `http://${window.location.hostname || "localhost"}:3001`
  //     : "http://localhost:3001";
  // }

  // // backendURL = 'https://backend.mytime2cloud.com/api';

  // backendURL = "http://192.168.2.67:8000/api";

  // console.log("backendURL", backendURL);

  // const isClient = typeof window !== "undefined";

  // const backendURL = isClient
  //   ? `${window.location.protocol}//${window.location.hostname}:8000/api`
  //   : "http://localhost:8000/api";

  // const appURL = isClient
  //   ? `${window.location.protocol}//${window.location.hostname}:3001`
  //   : "http://localhost:3001";

  // console.log("backendURL", backendURL);
  // console.log("appURL", appURL);

  // inject("backendUrl", backendURL);
  // inject("appUrl", appURL);

  const isClient = process.client;

  const backendURL = isClient
    ? `${window.location.protocol}//${window.location.hostname}:8000/api`
    : process.env.NUXT_ENV_BACKEND_URL || "http://192.168.2.67:8000/api";

  const appURL = isClient
    ? `${window.location.protocol}//${window.location.hostname}:3001`
    : process.env.NUXT_ENV_APP_URL || "http://192.168.2.67:3001";

  inject("backendUrl", backendURL);
  inject("appUrl", appURL);

  $axios.onRequest((config) => {
    config.baseURL = backendURL;

    const user = store.state.auth.user;

    // Ensure params exists
    config.params = config.params || {};

    if (!user) return config;

    if (user?.role?.role_type == "guard") {
      if (user && user.employee && user.employee.branch_id > 0) {
        config.params = {
          ...config.params,
          branch_id: user.employee.branch_id,
        };
      }
    }
    if (user && user.branch_id && user.branch_id > 0) {
      config.params = {
        ...config.params,
        branch_id: user && user.branch_id,
      };
    }

    /**
     * 1. Always add company_id
     */
    if (user.company_id) {
      config.params.company_id = user.company_id;
    }

    /**
     * 2. Single branch
     */
    if (user.branch_id && user.branch_id > 0) {
      config.params.branch_id = user.branch_id;
    }

    /**
     * 3. Department user
     */
    if (user.user_type === "department") {
      config.params.department_id = user.department_id;
      config.params.user_type = "department";
    }

    /**
     * 4. Multiple branches (branch_ids)
     */
    if (Array.isArray(user.branches) && user.branches.length > 0) {
      config.params.branch_ids = user.branches.map((b) => b.id);
    } else if (
      Array.isArray(config.params.branch_ids) &&
      config.params.branch_ids.length > 0
    ) {
      // Keep existing branch_ids passed from API calls
      config.params.branch_ids = config.params.branch_ids;
    }

    /**
     * 5. Multiple departments (department_ids)
     */
    if (Array.isArray(user.departments) && user.departments.length > 0) {
      config.params.department_ids = user.departments.map((d) => d.id);
      // config.params.branch_ids = user.departments.map(b => b.branch_id);
    } else if (
      Array.isArray(config.params.department_ids) &&
      config.params.department_ids.length > 0
    ) {
      // Keep existing department_ids passed from API calls
      config.params.department_ids = config.params.department_ids;
    }
    console.log("AXIOS ->", config.method?.toUpperCase(), config.url);
    return config;
  });

  $axios.onError((err) => {
    console.log("AXIOS ERR ->", err.response?.status, err.config?.url);
  });
};
