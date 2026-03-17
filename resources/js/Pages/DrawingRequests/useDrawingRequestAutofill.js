import { computed } from 'vue';

function normalizeId(value) {
  return value === null || value === undefined || value === '' ? '' : String(value);
}

function formatAddressParts(record) {
  if (!record) {
    return '';
  }

  const cityStateZip = [record.city, record.state, record.zip].filter(Boolean).join(', ');

  return [record.address, cityStateZip, record.country].filter(Boolean).join(', ');
}

export function useDrawingRequestAutofill(props, form) {
  const lastAutofill = {
    job_number: '',
    customer_address: '',
  };

  const selectedProject = computed(() =>
    props.projects.find((project) => normalizeId(project.id) === normalizeId(form.project_id))
  );

  function applyFieldAutofill(field, value, preserveManualValue = true) {
    const nextValue = value ?? '';
    const currentValue = form[field] ?? '';

    if (!preserveManualValue || currentValue === '' || currentValue === lastAutofill[field]) {
      form[field] = nextValue;
    }

    lastAutofill[field] = nextValue;
  }

  function findCustomer(customerId) {
    return props.customers.find((customer) => normalizeId(customer.id) === normalizeId(customerId));
  }

  function autofillFromCustomer(customerId, preserveManualValue = true) {
    const customer = findCustomer(customerId);

    applyFieldAutofill('customer_address', formatAddressParts(customer), preserveManualValue);
  }

  function autofillFromProject(projectId, preserveManualValue = true) {
    const project = props.projects.find(
      (entry) => normalizeId(entry.id) === normalizeId(projectId)
    );

    if (!project) {
      applyFieldAutofill('job_number', '', preserveManualValue);

      return;
    }

    if (normalizeId(project.customer_id) !== normalizeId(form.customer_id)) {
      form.customer_id = project.customer_id;
    }

    applyFieldAutofill('job_number', project.project_number ?? '', preserveManualValue);

    const customer = findCustomer(project.customer_id);
    const projectAddress = formatAddressParts(project);
    const fallbackCustomerAddress = formatAddressParts(customer);

    applyFieldAutofill(
      'customer_address',
      projectAddress || fallbackCustomerAddress,
      preserveManualValue
    );
  }

  function onCustomerChange() {
    if (
      selectedProject.value &&
      normalizeId(selectedProject.value.customer_id) !== normalizeId(form.customer_id)
    ) {
      form.project_id = '';
      applyFieldAutofill('job_number', '', false);
    }

    autofillFromCustomer(form.customer_id, true);
  }

  function onProjectChange() {
    autofillFromProject(form.project_id, true);
  }

  return {
    filteredProjects: computed(() => {
      if (!form.customer_id) {
        return props.projects;
      }

      return props.projects.filter(
        (project) => normalizeId(project.customer_id) === normalizeId(form.customer_id)
      );
    }),
    autofillFromProject,
    onCustomerChange,
    onProjectChange,
  };
}
