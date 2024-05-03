import PropTypes from 'prop-types'
import { useState, useCallback, useEffect } from 'react'
import { apiV2 } from 'fetch-api'

import { useApiQuery, useEntityId } from 'hooks'
import SearchBox from 'components/forms/SearchBox'

export default function EntitySearchField({ onEntitySelect, searchQueryType }) {
	const entityId = useEntityId()
	const [searchTerm, setSearchTerm] = useState('')
	const [defaultOptions, setDefaultOptions] = useState([])
	const onSearchTermChange = useCallback((newSearchTerm) => setSearchTerm(newSearchTerm), [setSearchTerm])
	const apiMethod = (!searchQueryType
		? apiV2.entity.search.labByName
		: searchQueryType
	)

	const onSelectInternal = useCallback((entity) => {
		onEntitySelect(entity?.id, entity?.name)
	}, [onEntitySelect])

	const filterEntitiesQuery = useCallback((axiosResponse) => {
		axiosResponse.data = axiosResponse.data.filter((entity) => {
			const isCurrentEntity = entity.id === entityId
			return !isCurrentEntity
		})

		return axiosResponse
	}, [entityId])

	const queryOptions = { enabled: !!searchTerm, refetchOnWindowFocus: false, refetchOnMount: false, retry: false, cacheTime: 0, select: filterEntitiesQuery }
	const entitySearch = useApiQuery(apiMethod, apiMethod.key, [searchTerm, 0, 100], queryOptions)

	const entityGetMostRecent = useApiQuery(apiV2.entity.get.labByMostRecent, 'entityGetMostRecent', [entityId])

	useEffect(() => {
		if (entityGetMostRecent.success) {
			const data = entityGetMostRecent.data
			const options = data.map((product) => ({
				value: product.id,
				label: product.name
			}))
			setDefaultOptions(options)
		}
	}, [entityGetMostRecent.success])

	const loadOptions = (searchValue, callback) => {
		if (entitySearch.success) {
			const data = entitySearch.data
			const options = data.map((entity) => ({
				value: entity.id,
				label: entity.name
			}))
			callback(options)
		}
	}

	const handleEntitySelect = (entitySelected) => {
		if (entitySelected === null) {
			return
		}
		const selected = entityGetMostRecent.data.find((entity) => entity.id === entitySelected.value)
		onEntitySelect(selected?.id, selected?.name)
	}

	return (
		<SearchBox
			label='Entity Search'
			placeholder='Search for an entity by name'
			isLoading={entitySearch.loading}
			onInputChange={onSearchTermChange}
			onChange={handleEntitySelect}
			loadOptions={loadOptions}
			onSelect={onSelectInternal}
			defaultOptions={defaultOptions}
			defaultInputValue=''
		/>
	)
}

EntitySearchField.propTypes = {
	onEntitySelect: PropTypes.func.isRequired,
	searchQueryType: PropTypes.func
}

EntitySearchField.defaultProps = {
	searchQueryType: null
}
