Main = {
    xhr: null,
    localeData: null,
    lastSelectedCountry: null,
    animationDuration: 1000,
    create: function() {
        // For new modal, defaults to US -- for onchange function
        this.lastSelectedCountry = "US";
        this.showCreateModal();
    },
    edit: function(data) {
        this.lastSelectedCountry = data.country;
        this.showEditModal(data);
    },
    submitEditForm: function() {
        let formObject = document.querySelector('#user_entry');
        let data = new FormData(formObject);
        this.updateUser(data);
    },
    submitCreateForm: function() {
        let formObject = document.querySelector('#user_entry');
        let data = new FormData(formObject);
        this.createUser(data);
    },
    delete: function(id) {
        let confirmDelete = confirm("Are you sure you want to delete this record?");
        if(!confirmDelete) {
            return false;
        }

        let self = this;
        this.xhrFetch('process_user_form.php?action=delete&id=' + id).then(function(resp) {
            if(resp === 'success') {
                let dataRow = document.querySelector('#user_' + id);
                dataRow.parentNode.removeChild(dataRow);
                self.prettyAlert("User successfully deleted.");
            } else {
                self.prettyAlert(resp);
            }
        });
    },
    modalFadeInAnimation: function(modal) {
        modal.style.display = 'block';
        modal.classList.add('fadein');
        document.querySelector('.action_modal_back').style.display = 'block';

        setTimeout(() => {
            modal.classList.remove('fadein');
        }, this.animationDuration);
    },
    closeModal: function() {
        let modal = document.querySelector('.action_modal');
        modal.classList.add('fadeout');
        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.remove('fadeout');
            document.querySelector('.action_modal_back').style.display = 'none';
        }, this.animationDuration);
    },
    showCreateModal: async function() {
        let modal = document.querySelector('.action_modal');
        this.modalFadeInAnimation(modal);

        this.xhrFetch('create_form.php').then(function(createHTML) {
            modal.innerHTML = createHTML;
        });
    },
    showEditModal: async function(data) {
        let modal = document.querySelector('.action_modal');
        this.modalFadeInAnimation(modal);

        this.xhrFetch('edit_form.php?data=' + JSON.stringify(data)).then(function(editHTML) {
            if(self.xhr.readyState === 4 && self.xhr.status === 200) {
                modal.innerHTML = editHTML;
            }
        });
        
        document.querySelector('.action_modal_back').style.display = 'block';
    },
    updateStates: async function() {
        let countrySelect = document.querySelector('#country');
        let country = countrySelect.options[countrySelect.selectedIndex].value;

        if(country !== this.lastSelectedCountry) {
            await this.fetchStates(country);
            this.lastSelectedCountry = country;
        }
        // Removes existing select from DOM
        let stateContainer = document.querySelector('#state_field_container');
        stateContainer.removeChild(document.querySelector('#state'));

        let newSelect = document.createElement('select');
        newSelect.setAttribute('id', 'state');
        newSelect.setAttribute('name', 'state');

        for(let state in this.localeData) {
            if(this.localeData.hasOwnProperty(state)) {
                let stateOption = document.createElement('option');
                stateOption.setAttribute('value', this.localeData[state].state_name);
                stateOption.text = this.localeData[state].state_name;
                newSelect.appendChild(stateOption);
            }
        }
        stateContainer.appendChild(newSelect);
    },
    fetchStates: async function(country) {
        let self = this;
        this.xhrFetch('getStates.php?country=' + country).then(function(resp){
            self.localeData = JSON.parse(resp);
        });
    },
    createUser: function(data) {
        let self = this;
        this.xhrPromisedPost('process_user_form.php?action=create', data).then(function() {
            if(self.xhr.readyState === 4 && self.xhr.status === 200) {
                let response = JSON.parse(self.xhr.responseText);
                if(response.error) {
                    self.outputFormError(response.error, response.dump);
                } else {

                    let usersTable = document.querySelector('#users_table');

                    // If no records was previously displayed, removes no records element, and creates table heading
                    let noRecords = document.querySelector('#no_records')
                    if(noRecords) {
                        noRecords.parentNode.removeChild(noRecords);
                        self.xhrFetch('get_table_heading.php?data=' + JSON.stringify(response.data)).then(function(resp) {
                            usersTable.innerHTML = resp;

                            // Take passed data, and form table row to insert
                            let newTableRow = document.createElement("tr");
                            newTableRow.setAttribute('id', 'user_' + response.data.id);
                            newTableRow.innerHTML = self.createTableRowCells(response.data);
                            usersTable.appendChild(newTableRow);
                            self.closeModal();
                            self.prettyAlert("User successfully created.");
                        });

                    // Otherwise appends row to table
                    } else {
                    
                        // Take passed data, and form table row to insert
                        let newTableRow = document.createElement("tr");
                        newTableRow.setAttribute('id', 'user_' + response.data.id);
                        newTableRow.innerHTML = self.createTableRowCells(response.data);
                        usersTable.appendChild(newTableRow);
                        self.closeModal();
                        self.prettyAlert("User successfully created.");
                    }

                }
            }
        })
    },
    updateUser: function(data) {
        let self = this;
        this.xhrPromisedPost('process_user_form.php?action=update', data).then(function() {
            if(self.xhr.readyState === 4 && self.xhr.status === 200) {
                let response = JSON.parse(self.xhr.responseText);
                if(response.error) {
                    self.outputFormError(response.error, response.dump);
                } else {
                    let usersTableRow = document.querySelector('#user_' + response.data.id);
                    usersTableRow.innerHTML = self.createTableRowCells(response.data);
                    self.closeModal();
                    self.prettyAlert("User Updated.");
                }
            }
        })
    },
    createTableRowCells: function(data) {
        let html = "";
        let i = 0;
        for(let property in data) {
            if(data.hasOwnProperty(property)) {
                html += "<td class='column" + i + "'>" + data[property] + "</td>";
                i++;
            }
        }
        html += "<td class='icon flink' onclick='Main.edit(" + JSON.stringify(data) + ")'>📝</td>"
            + "<td class='icon flink' onclick='Main.delete(" + data['id'] + ")'>🗑️</td>";
        return html;
    },
    xhrPromisedPost: function(url, data) {
        let self = this;
        return new Promise(function(resolve, reject) {
            self.xhr = new XMLHttpRequest();
            self.xhr.open("POST", url, false);
            self.xhr.onreadystatechange = function() {
                if(self.xhr.readyState === 4 && self.xhr.status === 200) {
                    resolve(self.xhr.responseText);
                } else {
                    reject(self.xhr.responseText);
                }
            }
            self.xhr.send(data);
        });
    },
    xhrFetch: function(url) {
        return new Promise(function(resolve, reject) {
            this.xhr = new XMLHttpRequest();
            this.xhr.open("GET", url, false);
            let self = this;
            this.xhr.onreadystatechange = function() {
                if(self.xhr.readyState === 4 && self.xhr.status === 200) {
                    resolve(self.xhr.responseText);
                } else {
                    reject(self.xhr.responseText);
                }
            }
            this.xhr.send();
        });
    },
    outputFormError: function(error, dump) {
        // Update inner HTML of form error element
        document.querySelector('#form_error').innerHTML = error;

        // Field names/ids are added to dump for form errors
        if(dump && dump.field) {
            document.querySelector('#' + dump.field).focus();
        }
    },
    prettyAlert: function(msg) {
        let modal = document.querySelector('#msg_modal');
        modal.style.display = 'block';
        modal.classList.add('slidein');
        document.querySelector('#modal_message').innerHTML = msg;
        let self = this;
        setTimeout(() => {
            modal.classList.remove('slidein');
            modal.classList.add('slideout');
            setTimeout(() => {
                modal.classList.remove('slideout');
                modal.style.display = 'none';
            }, self.animationDuration)
        }, 5000);
    }
}