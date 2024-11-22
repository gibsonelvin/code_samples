import { useState, useCallback, useEffect } from 'react'
import PropTypes from 'prop-types'
import { apiV2 } from 'fetch-api'

import { useApiQuery, useUser } from 'hooks'
import SearchBox from 'components/forms/SearchBox'

export default function UserSearchField({ onUserSelect, searchQueryType }) {
	const userId = useUser()
	const [searchTerm, setSearchTerm] = useState('')
	const [defaultOptions, setDefaultOptions] = useState([])
	const onSearchTermChange = useCallback((newSearchTerm) => setSearchTerm(newSearchTerm), [setSearchTerm])
	const apiMethod = (!searchQueryType
		? apiV2.user.search.all
		: searchQueryType
	)

	const onSelectInternal = useCallback((user) => {
		onUserSelect(user?.id, user?.firstName)
	}, [onUserSelect])

	const filterEntitiesQuery = useCallback((axiosResponse) => {
		axiosResponse.data = axiosResponse.data.filter((user) => {
			const isCurrentUser = user.id === userId
			return !isCurrentUser
		})

		return axiosResponse
	}, [userId])

	const queryOptions = { enabled: !!searchTerm, refetchOnWindowFocus: false, refetchOnMount: false, retry: false, cacheTime: 0, select: filterEntitiesQuery }
	const userSearch = useApiQuery(apiMethod, apiMethod.key, [searchTerm, 0, 100], queryOptions)
	const userGetAll = useApiQuery(apiV2.user.get.all, 'userGetAll', [userId])

	useEffect(() => {
		if (userGetAll.success) {
			const data = userGetAll.data
			const options = data.map((product) => ({
				value: product.id,
				label: product.name
			}))
			setDefaultOptions(options)
		}
	}, [userGetAll.success])

	const loadOptions = (searchValue, callback) => {
		if (userSearch.success) {
			const data = userSearch.data
			const options = data.map((user) => ({
				value: user.id,
				label: user.firstName
			}))
			callback(options)
		}
	}

	const handleUserSelect = (userSelected) => {
		if (userSelected === null) {
			return
		}
		const selected = userGetAll.data.find((user) => user.id === userSelected.value)
		onUserSelect(selected?.id, selected?.name)
	}

	return (
		<SearchBox
			label='User Search'
			placeholder='Search for an user by name'
			isLoading={userSearch.loading}
			onInputChange={onSearchTermChange}
			onChange={handleUserSelect}
			loadOptions={loadOptions}
			onSelect={onSelectInternal}
			defaultOptions={defaultOptions}
			defaultInputValue=''
		/>
	)
}

UserSearchField.propTypes = {
	onUserSelect: PropTypes.func.isRequired,
	searchQueryType: PropTypes.func
}

UserSearchField.defaultProps = {
	searchQueryType: null
}
