import React, { useCallback, useState, useEffect } from 'react'
import { useApiMutation, useEntity } from 'hooks'
import { toast } from 'react-toastify'
import { Form, Button, Dialog, Alert } from 'kit'
import EntitySearchField from 'components/EntitySearchField'
import PropTypes from 'prop-types'
import { apiV2 } from 'fetch-api'
import AddDataRequestConstituentForm from './AddDataRequestConstituentForm'

export default function CreateDataRequestDialog({ createDialogShowing, closeMethod, dataFileTypes, refreshMethod }) {
	const [newDataRequestEntity, setNewDataRequestEntity] = useState(null)
	const [constituentFormShowing, setConstituentFormShowing] = useState(false)
	const [newConstituents, setNewConstituents] = useState([])
	const [constituentDisplay, setConstituentDisplay] = useState(null)
	const [errorMessage, setErrorMessage] = useState(null)
	const entity = useEntity()
	const createDataRequestCall = useApiMutation(apiV2.dataRequest.create)

	const toggleConstituentForm = () => {
		setConstituentFormShowing(!constituentFormShowing)
	}

	const resetInput = (e) => {
		e.preventDefault()
		setNewDataRequestEntity(null)
		setNewConstituents([])
		closeMethod()
		if (constituentFormShowing) toggleConstituentForm()
	}

	const getReadableDataFileType = (id) => {
		for (const dataFileType of dataFileTypes.data) {
			if (Number(id) === Number(dataFileType.id)) {
				return (
					<span>
						{dataFileType.extensions.substr(0, 12) + (dataFileType.extensions.length > 12 ? '...' : '')}
					</span>
				)
			}
		}
	}

	const renderNewConstistuentsDisplay = () => {
		let constituentDisplayElements = null
		if (newConstituents.length === 0) {
			constituentDisplayElements = (<span className='col-span-3 text-xs pt-5 text-center'>-No files attached to this request. Click the button below to begin.</span>)
		}
		else {
			constituentDisplayElements = [(
				<React.Fragment key='constituent_display_header_row'>
					<div className='col-span-2 bg-slate-200 px-5 h-5'>File Description/Label:</div>
					<div className='col-span-1 bg-slate-200 px-5 h-5'>File Format:</div>
				</React.Fragment>
			)]

			let key = 0
			for (const constituent of newConstituents) {
				const labelColumnClass = `${key % 2 === 0 ? 'bg-slate-50' : ''} col-span-2 px-5 h-5`
				const typeColumnClass = `${key % 2 === 0 ? 'bg-slate-50' : ''} col-span-1 px-5 h-5`
				constituentDisplayElements.push(
					(
						<React.Fragment key={key}>
							<div className={labelColumnClass}>{constituent.label}</div>
							<div className={typeColumnClass}>{getReadableDataFileType(constituent.fileType)}</div>
						</React.Fragment>
					)
				)
				key += 1
			}
		}
		return (
			<div className='grid grid-cols-3 text-xs shadow-[0_0_3px_0px_#CCC] h-20 overflow-y-scroll place-content-start' key='main_constituent_table'>
				{constituentDisplayElements}
			</div>
		)
	}

	const reportError = (title, msg) => {
		setErrorMessage((
			<Alert variant='danger' className='w-fit max-w-2xl'>
				<Alert.Title>{title}</Alert.Title>
				<Alert.Body>{msg}</Alert.Body>
			</Alert>
		))
	}

	const createDataRequest = useCallback((e) => {
		e.preventDefault()
		if (newDataRequestEntity === null) {
			reportError('Please select an Entity for this request.')
			return false
		}
		if (newConstituents.length === 0) {
			reportError('Please enter details about the file(s) you\'re requesting.')
			return false
		}

		const dataRequestDetails = {
			requestedEntityId: newDataRequestEntity,
			constituents: newConstituents
		}

		// Clears error message if present
		if (errorMessage !== null) {
			setErrorMessage(null)
		}

		e.target.disabled = true
		toast.promise(createDataRequestCall.callAsync(entity.id, dataRequestDetails), {
			pending: 'Creating Data Request',
			success: 'Data Request created',
			error: 'Failed to create Data Request'
		}).then((responseData) => {
			e.target.disabled = false
			const { updatedDataRequest } = responseData.data
			refreshMethod(updatedDataRequest)
			resetInput(e)
		})
	})

	const selectEntityFromSearch = useCallback((id) => {
		setNewDataRequestEntity(id)
		setErrorMessage(null)
	})

	const updateConstituents = (constituents) => {
		setNewConstituents(constituents)
		setConstituentDisplay(renderNewConstistuentsDisplay())
		setErrorMessage(null)
	}

	useEffect(() => {
		setConstituentDisplay(renderNewConstistuentsDisplay(newConstituents))
	}, [newConstituents])

	return (
		<Dialog open={createDialogShowing}>
			<Dialog.Content className='overflow-y-auto' onClose={closeMethod}>
				<Dialog.Header>
					<Dialog.Title>Create A Data Request</Dialog.Title>
					<Dialog.Description>Enter details for data request.</Dialog.Description>
				</Dialog.Header>
				{errorMessage}
				<Form>
					<EntitySearchField onEntitySelect={selectEntityFromSearch} searchQueryType={apiV2.entity.search.labByName} />
					<p className='pt-10'>Files requested:</p>
					{constituentDisplay}
					<AddDataRequestConstituentForm showing={constituentFormShowing} toggleMethod={toggleConstituentForm} constituents={newConstituents} onConstituentUpdate={updateConstituents} dataFileTypes={dataFileTypes} updateDisabled={false} />
					<Button className='w-full lg:w-2/5 mt-5 float-left ml-10' variant='outline' size='sm' onClick={(event) => resetInput(event)}>Cancel</Button>
                    &nbsp;
					<Button className='w-full lg:w-2/5 mt-5 float-right mr-10' variant='primary' size='sm' onClick={(event) => createDataRequest(event)} disabled={false}>Create Data Request</Button>
				</Form>
			</Dialog.Content>
		</Dialog>
	)
}

CreateDataRequestDialog.propTypes = {
	createDialogShowing: PropTypes.bool.isRequired,
	closeMethod: PropTypes.func.isRequired,
	dataFileTypes: PropTypes.shape({
		data: PropTypes.array
	}).isRequired,
	refreshMethod: PropTypes.func.isRequired
}
