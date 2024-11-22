import { useCallback, useState } from 'react'
import { apiV2 } from 'fetch-api'
import { Container, Button, Table, Form, Dialog, Icon } from 'kit'
import { useApiQuery, useManagedDrawer } from 'hooks'
import { enums } from 'utilities'
import EntityDisplay from 'components/EntityDisplay'
import UserDisplay from 'components/UserDisplay'

import useSelectedEntity from './useSelectedEntity'
import useSelectedUser from './useSelectedUser'
import EntitySearchField from './EntitySearchField'
import UserSearchField from './UserSearchField'

export default function ViewAll() {
	const { DEFAULT_PAGE_SIZE } = enums
	const [searchFieldShowing, setSearchFieldShowing] = useState(false)
	const [selectedEntityDetails, setDetailsEntity] = useState([])
	const [page, setPage] = useState(0)
	const [view, setView] = useState('entities')
	const entitiesSelected = (view === 'entities')
	const apiMethod = (view === 'entities'
		? apiV2.entity.get.all
		: apiV2.user.get.all
	)
	const entities = useApiQuery(apiMethod, apiMethod.key, [null, page, DEFAULT_PAGE_SIZE])
	const rowData = entities.success ? entities.data : null
	const selectedEntity = (entitiesSelected
		? useSelectedEntity(selectedEntityDetails)
		: useSelectedUser(selectedEntityDetails)
	)
	const drawer = useManagedDrawer(() => {}, { noPadding: true })
	const showEntitySearchModal = useCallback(() => { setSearchFieldShowing(true) })
	const hideEntitySearchModal = useCallback(() => { setSearchFieldShowing(false) })

	const selectEntityFromSearch = useCallback((id, name) => {
		hideEntitySearchModal()
		setDetailsEntity([id, name])
	})

	const rowSelect = (grid) => {
		setDetailsEntity([grid.data.id, grid.data.name])
	}
	const hideDetailView = () => {
		selectEntityFromSearch(false, false)
	}
	const ViewSelector = (
		<div className='w-full grid grid-cols-1'>
			<div className='w-48 place-self-center'>
				<input id='viewEntitiesButton' type='radio' name='view' value='entities' onChange={() => setView('entities')} checked={entitiesSelected} />
				<label htmlFor='viewEntitiesButton'> Non-User Entities</label>
			</div>

			<div className='w-48 place-self-center'>
				<input id='viewUserButton' type='radio' name='view' value='users' onChange={() => setView('users')} checked={!entitiesSelected} />
				<label htmlFor='viewUserButton'> Users</label>
			</div>
		</div>
	)

	if (!rowData) {
		return (view === 'entities'
			? (
				<>
					<h1 className='text-3xl pb-4 text-center'>No entities to show</h1>
					{ViewSelector}
				</>
			)
			: (
				<>
					<h1 className='text-3xl pb-4 text-center'>No users to show</h1>
					{ViewSelector}
				</>
			)
		)
	}

	const searchQuery = (view === 'entities'
		? apiV2.entity.search.labByName
		: apiV2.user.search.all
	)

	const defaultColDef = {
		sortable: true,
		filter: true,
		resizable: true,
		maxWidth: 150
	}

	const drawerDetails = (entitiesSelected
		? (<EntityDisplay entity={selectedEntity} />)
		: (<UserDisplay user={selectedEntity} />)
	)

	let columnDefs = [
		{ field: 'name', flex: 2, minWidth: 12, maxWidth: 600 },
		{ field: 'email', flex: 2, minWidth: 5, maxWidth: 600 }
	]

	// Defines user table columns
	if (!entitiesSelected) {
		columnDefs = [
			{ field: 'firstName', flex: 2, minWidth: 12, maxWidth: 300 },
			{ field: 'lastName', flex: 2, minWidth: 12, maxWidth: 300 },
			{ field: 'email', flex: 2, minWidth: 5, maxWidth: 600 }
		]
	}

	let displayName = null
	let entityDetailsView = null

	if (selectedEntity.data) {
		displayName = (entitiesSelected
			? selectedEntity.data.name
			: `${selectedEntity.data.firstName} ${selectedEntity.data.lastName}`
		)

		entityDetailsView = (
			<article style={drawer.style} className={drawer.className}>
				<h1 className='text-xl text-center pt-4 leading-none mb-4'>
					{displayName}
				</h1>
				<h1 className='text-xl text-center'>View Details Below:</h1>
				{drawerDetails}
				<Button className='w-full' variant='outline' size='sm' onClick={hideDetailView}>Close</Button>
			</article>
		)
	}
	const searchField = (entitiesSelected
		? (<EntitySearchField onEntitySelect={selectEntityFromSearch} searchQueryType={searchQuery} />)
		: (<UserSearchField onUserSelect={selectEntityFromSearch} searchQueryType={searchQuery} />)
	)

	const searchDisplay = searchFieldShowing ? (
		<Dialog open>
			<Dialog.Overlay />
			<Dialog.Content className='overflow-y-auto'>
				<h1 className='text-xl leading-none mb-4'>Search for an entity</h1>
				<Dialog.Description>Click the text field, and start typing the entity name to search.</Dialog.Description>
				<Form noStyle className='my-4 flex flex-col gap-4' onSubmit={() => {}} id='entity_search_view_all'>
					{searchField}
					<div className='mt-2 flex flex-col md:flex-row gap-4'>
						<Button className='w-full' variant='outline' size='sm' onClick={hideEntitySearchModal}>Cancel</Button>
					</div>
				</Form>
			</Dialog.Content>
		</Dialog>
	) : null

	return (

		<Container>
			{entityDetailsView}
			{searchDisplay}
			<h1 className='text-3xl pb-4 text-center'>
				Viewing All:
			</h1>
			{ViewSelector}
			<div dir='rtl'>
				<Button className='mb-4 right-0 start-0' onClick={showEntitySearchModal}>
					Search
					<Icon className='mr-1' icon='iconamoon:search-duotone' />
				</Button>
			</div>
			<Table
				rowHeight={28}
				className='h-[80vh]'
				rowData={rowData}
				columnDefs={columnDefs}
				defaultColDef={defaultColDef}
				onNewRowSelect={rowSelect}
				pagination
				page={page}
				pageSize={DEFAULT_PAGE_SIZE}
				totalRows={entities.data?.count}
				onPaginate={setPage}
			/>
		</Container>
	)
}
