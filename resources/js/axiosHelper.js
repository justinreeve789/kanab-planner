import axios from 'axios';

class AxiosHelper {
  constructor(baseURL = '') {
    this.instance = axios.create({
      baseURL: baseURL,
    });

    this.activeGetRequests = new Map();

    this.onRouteChange = () => {
      this.cancelAllGetRequests();
    };
  }

  /**
   * GET that is cancelled when the route changes. Pass { persistent: true } for requests that must survive
   * navigation (e.g. who is signed in).
   */
  get(url, config = {}) {
    const { persistent, ...rest } = config;
    if (persistent) {
      return this.instance.get(url, rest);
    }
    config = rest;
    const controller = new AbortController();
    const signal = controller.signal;
    this.activeGetRequests.set(url, controller);

    const promise = this.instance.get(url, { ...config, signal })
      .finally(() => {
        this.activeGetRequests.delete(url);
      });

    promise.catch(error => {
      if (error?.name === 'AbortError') {
        console.log(`GET request to ${url} was aborted.`);
      }
    });

    return promise;
  }

  post(url, data, config = {}) {
    return this.instance.post(url, data, config);
  }

  put(url, data, config = {}) {
    return this.instance.put(url, data, config);
  }

  delete(url, config = {}) {
    return this.instance.delete(url, config);
  }

  /**
   * Cancels all currently active GET requests.
   */
  cancelAllGetRequests() {
    for (const controller of this.activeGetRequests.values()) {
      controller.abort();
    }
    this.activeGetRequests.clear();
    console.log('All active GET requests have been cancelled.');
  }

  /**
   * Cancels a specific GET request by its URL.
   * @param {string} url The URL of the request to cancel.
   */
  cancelGetRequest(url) {
    const controller = this.activeGetRequests.get(url);
    if (controller) {
      controller.abort();
      this.activeGetRequests.delete(url);
      // console.log(`GET request to ${url} has been cancelled.`);
      return true; // Indicate successful cancellation
    }
    return false; // Request not found or already completed
  }
}

const axiosHelperInstance = new AxiosHelper('/api/v1');

export default axiosHelperInstance;