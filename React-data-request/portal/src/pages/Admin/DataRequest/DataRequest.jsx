import { useCallback, useState } from 'react'
import { apiV2 } from 'fetch-api'
import { useEntity, useApiQuery } from 'hooks'
import { Container, Button, Table, Icon } from 'kit'
import { enums } from 'utilities'
import { isInteger } from 'lodash'
import useSelectedDataRequest from './useSelectedDataRequest'
import CreateDataRequestDialog from './CreateDataRequestDialog'
import DataRequestDetailsDrawer from './DataRequestDetailsDrawer'

const { DEFAULT_PAGE_SIZE, DATA_REQUEST } = enums

export default function DataRequest() {
	const page = 0
	const [selectedDataRequest, setSelectedDataRequest] = useState(null)
	const [createDialogShowing, setCreateDialogShowing] = useState(false)

	const entity = useEntity()
	const sentOrReceived = (entity.entityTypeId === enums.ENTITY_TYPE.AUDIT
		? 'sent'
		: 'received'
	)

	const getAllApiMethod = (sentOrReceived === 'sent'
		? apiV2.dataRequest.get.sent
		: apiV2.dataRequest.get.received
	)

	const dataRequestConstituents = useSelectedDataRequest(selectedDataRequest)
	const requests = useApiQuery(getAllApiMethod, getAllApiMethod.key, [entity.id], [null, page, DEFAULT_PAGE_SIZE])
	const dataFileTypes = useApiQuery(apiV2.dataFileType.get.all, apiV2.dataFileType.get.all.key)
	if (dataFileTypes.data && dataFileTypes.success) {
		dataFileTypes.data.map((element) => {
			element.label = `${element.type} (${element.extensions})`
			element.value = element.id
			return element
		})
	}

	const convertStatus = (dataRequestObject) => {
		// Makes status readable, checks for integer so that it doesn't do it twice
		if (isInteger(dataRequestObject.status)) {
			dataRequestObject.status = DATA_REQUEST.STATUS_TEXT[dataRequestObject.status]
		}
		return dataRequestObject
	}

	const hideDetailView = useCallback(() => setSelectedDataRequest(null))
	const rowSelect = (grid) => {
		setSelectedDataRequest(grid.data)
	}

	const showCreateDialog = () => {
		setCreateDialogShowing(true)
	}

	const refreshDataRequests = (updatedDataRequest) => {
		updatedDataRequest = convertStatus(updatedDataRequest)

		requests.refetch()
		if (typeof updatedDataRequest !== 'undefined' && updatedDataRequest !== null) {
			setSelectedDataRequest(updatedDataRequest)
		}
	}

	const requestDetailsView = selectedDataRequest != null && (
		<DataRequestDetailsDrawer
			sentOrReceived={sentOrReceived}
			refreshMethod={refreshDataRequests}
			fileTypes={dataFileTypes}
			dataRequest={selectedDataRequest}
			constituents={dataRequestConstituents}
			hideDetailView={hideDetailView}
		/>
	)
	const createButton = sentOrReceived === 'sent'
		? (
			<p className='pb-10'>
				<Button type='button' className='mb-4 right-0 start-0' onClick={() => showCreateDialog()}>
					New Request&nbsp;
					{' '}
					<Icon className='mr-1' icon='mdi:plus-outline' />
				</Button>
			</p>
		)
		: null

	const columnDefs = [
		{ field: 'description', flex: 2, minWidth: 12, maxWidth: 800 },
		{ field: 'status', flex: 2, minWidth: 5, maxWidth: 200 },
		{ field: 'createdAt', flex: 2, minWidth: 12, maxWidth: 200 }
	]

	const defaultColDef = {
		sortable: true,
		filter: true,
		resizable: true,
		maxWidth: 150
	}

	const rowData = requests.success ? requests.data : null
	if (rowData) {
		rowData.map(convertStatus)
	}

	const title = (
		<h1 className='text-3xl pb-4 text-center'>
			Viewing
			{' '}
			{(sentOrReceived === 'sent' ? 'Sent' : 'Received')}
			{' '}
			Data Requests:
		</h1>
	)

	return (
		<Container>
			<CreateDataRequestDialog
				createDialogShowing={createDialogShowing}
				closeMethod={() => setCreateDialogShowing(false)}
				dataFileTypes={dataFileTypes}
				refreshMethod={refreshDataRequests}
			/>
			{requestDetailsView}
			{title}
			{createButton}
			<Table
				rowHeight={28}
				className='h-[60vh]'
				rowData={rowData}
				columnDefs={columnDefs}
				defaultColDef={defaultColDef}
				onNewRowSelect={rowSelect}
				totalRows={requests.data?.count}

				error={requests.error}
				errorTitle='Failed to get data requests'
				errorBody='Try again later or contact support'
			/>
		</Container>
	)
}
